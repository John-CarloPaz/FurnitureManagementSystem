# Project Plan & Architecture

**Project:** Web-Based Real-Time Monitoring System with 3D Model Integration for Furniture Product Manufacturing and Delivery
**Source of truth:** `New-Updated-Caps` (system behavior) + `Project_Quotation` (module list & scope)
**Status:** In build — M1 done; domain realigned 2026-07-07 (see banner)
**Last updated:** 2026-07-07

---

> ### ⚠ Domain realignment (2026-07-07)
> **Single-tenant** furniture company. Two distinct concerns, previously conflated into one "Order":
> - **Products (Catalog):** the company (Admin) prepares products — each with a **3D model** — and **publishes** them (DRAFT → PUBLISHED → ARCHIVED). Only published products are visible to customers.
> - **Orders (Fulfillment):** a customer places a **multi-item** order against published products; it moves through a **role-owned** manufacturing/delivery FSM (PLACED → CONFIRMED → IN_PRODUCTION → QUALITY_CHECK → READY_FOR_DELIVERY → OUT_FOR_DELIVERY → DELIVERED → COMPLETED).
> - **Pricing:** products have a price; orders compute totals + track downpayment/payment status (no gateway).
>
> The 3D model moved from Order → **Product**. Existing M2 code (State pattern, 3D upload/versioning, R3F viewer, RBAC) is **reused**. Execution plan: [ROADMAP.md](./ROADMAP.md) Phase R. Revised specs: [DATA_MODEL](./design/DATA_MODEL.md) · [FSM](./design/FSM.md) · [RBAC](./design/RBAC.md).

---

## 1. Locked Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Backend | **Laravel 11 (LTS)**, **API-only** | LTS security updates through 2027 (per caps §1.3); decoupled REST API |
| Frontend | **React 18 + Vite + TypeScript**, standalone repo | Modern, scalable; strongest 3D ecosystem (react-three-fiber) |
| Coupling | Fully decoupled — backend serves JSON only | Independent deploy/scale of API and UI |
| Auth | **Laravel Sanctum** (API tokens) | First-party token auth for SPA + future mobile (delivery app) |
| Real-time | **Pusher** (hosted) via Laravel Broadcasting → `pusher-js` + Laravel Echo | True push for shop-floor & delivery; caps allows WebSocket push |
| Database | **PostgreSQL 16** (local dev) | Chosen over the caps' MySQL; native declarative partitioning, stronger constraints/JSONB. DB `furniture_monitoring` on `127.0.0.1:5432` (user `postgres`) — created |
| RBAC | `spatie/laravel-permission` | Battle-tested 7-role model; scalable; less custom code |
| 3D | **Upload & view only** (react-three-fiber + drei) | System never generates models — users upload `.glb`/`.obj` |
| Docs | One `.md` per module under `docs/modules/` | Hard requirement |

**Two repos:**
```
furniture-monitoring-system/
├── backend/      # Laravel API-only
├── frontend/     # React + Vite SPA
└── docs/         # ARCHITECTURE.md, modules/*.md, this PLAN.md
```

---

## 2. Full Tech Stack

### Backend (Laravel API)
- PHP 8.2+, Laravel 11
- **DB:** PostgreSQL 16 — `pgsql` driver, DB `furniture_monitoring` @ `127.0.0.1:5432`, native **declarative partitioning** (`PARTITION BY RANGE` on order year) instead of MySQL partitioning
- **Auth:** Sanctum (personal access tokens)
- **RBAC:** spatie/laravel-permission
- **Broadcasting:** Pusher driver (`pusher/pusher-php-server`)
- **Queue:** database driver (dev) → Redis (prod) for notifications, email, DSS jobs
- **3D storage:** local disk (dev) → S3-compatible (prod); signed temporary URLs (1h expiry per caps §4.4)
- **Testing:** Pest / PHPUnit
- **Standards:** PSR-12, Larastan (static analysis), Laravel Pint (formatting)

### Frontend (React SPA)
- React 18 + Vite + TypeScript
- **Routing:** React Router
- **Server state:** TanStack Query (caching, polling fallback, invalidation)
- **UI state:** Zustand (light) + React Context for auth
- **Real-time:** `laravel-echo` + `pusher-js`
- **3D:** `three` + `@react-three/fiber` + `@react-three/drei` (OrbitControls, GLTF/OBJ loaders, Draco)
- **Forms/validation:** react-hook-form + zod
- **UI kit:** Tailwind CSS + shadcn/ui
- **Charts (KPI):** Recharts
- **QR:** `html5-qrcode` (scan) — delivery personnel
- **Testing:** Vitest + React Testing Library; Playwright for E2E

---

## 3. Domain Architecture (SOLID)

Backend is organized by **domain module**, not by Laravel's default type-folders. Each module is self-contained and depends on others only through **interfaces** and **events**.

```
backend/app/
├── Domain/
│   ├── Users/          # auth, roles, RBAC
│   ├── Orders/         # FSM lifecycle spine
│   ├── Models3D/       # upload, versioning, approval
│   ├── Manufacturing/  # stage tracking, work orders, scheduling
│   ├── Delivery/       # QR, GPS, proof-of-delivery
│   ├── Notifications/  # in-app + email
│   ├── Analytics/      # KPI computation
│   └── Dss/            # scheduling / workflow / route strategies
├── Http/               # Controllers (thin), Requests, Resources, Middleware
└── Providers/          # bind interfaces → implementations
```

Each `Domain/<Module>/` contains:
```
Actions/        # single-purpose use cases (e.g. ApproveModelForProduction)
Services/       # orchestration
Contracts/      # interfaces (repositories, strategies)
Repositories/   # Eloquent implementations of Contracts
Models/         # Eloquent models
DTOs/           # typed data across boundaries
Events/         # domain events (OrderTransitioned, StageDelayed, ...)
States/         # (Orders only) FSM state classes
```

### How each SOLID principle is enforced
- **S — Single Responsibility:** thin controllers → one Action per use case. A controller only validates (Form Request) and delegates.
- **O — Open/Closed:** FSM states, DSS strategies, and notification channels are added as **new classes**, never by editing a `switch`.
- **L — Liskov:** repositories and strategies are interchangeable behind their interface (Eloquent repo swappable for a fake in tests).
- **I — Interface Segregation:** small focused contracts (`SchedulingStrategy`, `RouteOptimizer`, `Model3DRepository`) rather than one god-interface.
- **D — Dependency Inversion:** Actions depend on `Contracts\*`, bound to concrete classes in a service provider. Enables the caps' "move to S3/RDS later without rewriting code" (§1.2).

### Cross-module communication = Events
Modules never call each other directly. The **Orders FSM** emits events; Notifications, Analytics, and Manufacturing **listen**. This keeps modules loosely coupled and independently testable.

```
OrderTransitioned ──► NotifyStakeholders (Notifications)
                 └──► RecordKpiSnapshot (Analytics)
StageDelayed ─────► AlertProductionManager (Notifications)
DeliveryCompleted ─► SendDeliveryEmail + CloseOrder
```

---

## 4. The FSM (Order Lifecycle) — the spine

Implemented as a **State pattern**: a `OrderState` interface, one class per state, each declaring `allowedTransitions()` and transition guards. Every transition is written to an `order_state_transitions` audit table (who, when, from→to) — satisfies BIR/DTI audit-trail requirement (caps §2.4).

```
DRAFT
  └─► PENDING_APPROVAL        (client must approve 3D model)
        ├─► APPROVED          (client clicks "Approve for Production")
        │     └─► IN_PRODUCTION
        │            ├─► QUALITY_CHECK
        │            │     ├─► READY_FOR_DELIVERY   (QC pass)
        │            │     └─► REWORK ─► IN_PRODUCTION  (QC fail)
        │            └─► (manufacturing sub-stages tracked separately)
        └─► CANCELLED
  READY_FOR_DELIVERY
        └─► OUT_FOR_DELIVERY
              └─► DELIVERED
                    └─► CLOSED
```

**Manufacturing sub-stages** (cutting → assembly → sanding → finishing → QC) live *inside* `IN_PRODUCTION` and are tracked in `manufacturing_stages` with start/end timestamps + automatic delay detection (caps §Expected Output #3). They are **not** FSM states — they're progress records, so the FSM stays small.

---

## 5. Data Model (outline)

Core tables (final schema in migrations during build):

| Table | Purpose | Notes |
|---|---|---|
| `users`, `roles`, `permissions`, `model_has_roles` | 7-role RBAC | spatie tables; encrypted PII at rest |
| `orders` | order spine | **Postgres declarative partition by year** (`PARTITION BY RANGE (created_at)`); `state`, `order_id` (public), timestamps |
| `orders_archive` | archive | rows > 1yr detached/moved here (caps §1.4) |
| `order_state_transitions` | FSM audit trail | who/when/from→to |
| `models_3d` | 3D asset per order | current version pointer |
| `model_3d_versions` | version history + change log | client + production see diffs (caps Expected Output #2) |
| `manufacturing_stages` | sub-stage tracking | stage, start/end, delay flag |
| `work_orders`, `production_schedules` | scheduling output | DSS writes here |
| `wood_batches`, `suppliers` | traceability | "Order #204 → wood_batch_#A45" (caps §2.2) |
| `delivery_assignments` | logistics → personnel | batching support (caps §3.3) |
| `delivery_events` | status/GPS/manual location log | |
| `proof_of_deliveries` | photo + signed receipt + timestamp | |
| `notifications` | in-app + email log | Laravel notifications table |
| `kpi_snapshots` | periodic KPI cache | feeds dashboard fast |

---

## 6. Modules → Build Order (dependency-driven)

Mapped to the 11 quotation modules. Build foundations first; DSS/KPI last (they read everything).

| # | Build phase | Quotation module(s) | Key deliverables |
|---|---|---|---|
| 1 | **Foundation** | #9 Database & API Layer | Migrations, partitioning, API skeleton, Sanctum, base Resources, error format, OpenAPI stub |
| 2 | **Access** | #5 Role-Based Auth & User Mgmt | 7 roles, encrypted login, policies/gates, user CRUD, RA 10173 notes |
| 3 | **Spine** | #4 Order Management (FSM) | State classes, transition guards, audit trail, order CRUD |
| 4 | **3D** | #1 3D Model Viewer | Upload (`.glb`/`.obj`), Draco, versioning, signed URLs, approval flow → drives FSM `APPROVED` |
| 5 | **Shop floor** | #2 Manufacturing Tracking + #10 Production Scheduling/Work Orders | Stage tracking, delay detection, work orders, real-time dashboard |
| 6 | **Last mile** | #3 Delivery Tracking | QR scan, GPS/manual, photo proof, delivery email |
| 7 | **Comms** | #7 Notification System | In-app + email, subscribed to FSM/stage events |
| 8 | **Insight** | #6 KPI & Analytics Dashboard | OTE, avg lead time, on-time rate, defect/rework rate |
| 9 | **Intelligence** | #8 Model-Driven DSS Engine | Scheduling + workflow + route strategies behind interfaces |
| 10 | **Cross-cutting** | #11 QA & Testing | Unit/integration/E2E, coverage target, security testing |

---

## 7. Real-time (Pusher) design

- Backend fires broadcast events on **private channels** scoped by role/order:
  - `private-order.{orderId}` → client sees "60% done – Finishing"
  - `private-shop-floor` → production manager dashboard
  - `private-deliveries` → logistics coordinator
- Channel authorization via Sanctum + `routes/channels.php` (only assigned users can subscribe → RBAC on the wire).
- Frontend: Laravel Echo (`broadcaster: 'pusher'`) subscribes; TanStack Query cache updated on event.
- **Polling fallback:** TanStack Query `refetchInterval` (10–30s) kept as backstop so the defense demo never depends on WebSocket availability.

---

## 8. Model-Driven DSS (#8) — approach

All three algorithm families sit behind interfaces so they're swappable/testable (Open/Closed + Dependency Inversion):

| Concern | Interface | Initial implementation |
|---|---|---|
| Production scheduling | `SchedulingStrategy` | Greedy heuristic (e.g. weighted shortest-processing-time) over work orders + resource constraints |
| Workflow / bottleneck | `WorkflowAnalyzer` | Detect the stage where most delays originate ("Assembly bottleneck: 70%") |
| Delivery routing | `RouteOptimizer` | Nearest-neighbor + batching by area ("3 orders → Balibago") |

DSS runs as **queued jobs** and writes results to `production_schedules` / `delivery_assignments`. Swapping in a smarter algorithm later means adding one class + one binding — no controller changes.

---

## 9. Documentation strategy (per-module `.md`)

Every module ships its own doc in `docs/modules/`, written **alongside** the code, plus:
- `docs/ARCHITECTURE.md` — system overview, diagrams, event map, FSM diagram
- `docs/README.md` — index + how the docs fit together
- `docs/API.md` — endpoint reference (or OpenAPI)

**Per-module template** (`docs/modules/_template.md`):
```markdown
# <Module Name>

## Purpose
What business problem it solves (link to caps section).

## Responsibilities
- Bullet list (single-responsibility boundaries)

## Public API
| Method | Route | Role(s) | Description |

## Domain classes
Actions / Services / Contracts / Repositories and what each does.

## Data
Tables owned, key columns, relationships.

## Events
Emitted: ...
Consumed: ...

## Real-time channels
Channels broadcast to and who may subscribe.

## SOLID notes
How this module applies the principles (esp. extension points).

## How to extend
Concrete "add a new X" walkthrough.

## Tests
What's covered and how to run.
```

---

## 10. Testing & Quality (#11)

- **Backend:** Pest feature tests per endpoint + unit tests for FSM guards, DSS strategies, KPI math. Larastan level ↑ over time. Target ≥ the caps' 95% coverage goal on core modules.
- **Frontend:** Vitest + RTL for components/hooks; Playwright E2E for the golden paths (order → approve 3D → produce → deliver).
- **Security:** RBAC policy tests (each role can only reach its modules — caps role table §Expected Output), signed-URL expiry test, input validation, RA 10173 checklist.
- **CI:** GitHub Actions — lint (Pint/ESLint) → static analysis → tests → build, on every push.

---

## 11. Proposed Milestones (aligned to caps' 5–6 month timeline)

| Milestone | Modules | Exit criteria |
|---|---|---|
| M1 — Foundation | #9, #5 | Auth works; 7 roles enforced; DB schema + partitioning live |
| M2 — Order spine | #4, #1 | Order created → 3D uploaded → client approves → FSM advances, all audited |
| M3 — Production | #2, #10 | Stage tracking + delay detection + real-time shop-floor dashboard |
| M4 — Delivery + Comms | #3, #7 | QR/proof-of-delivery + automated emails + in-app notifications |
| M5 — Insight + Intelligence | #6, #8 | Live KPIs + DSS scheduling/routing producing usable output |
| M6 — Hardening | #11 | Coverage target, security pass, docs complete, UAT sign-off, deploy |

---

## 12. Open items to confirm before scaffolding

1. **Pusher account** — you'll need a Pusher app (key/secret/cluster) for `.env`. Free tier is fine for the capstone. I'll add polling fallback so early dev doesn't block on this.
2. **Hosting** — caps says the client handles hosting (quotation note #8). Decoupled means the React build is static (Netlify/Vercel/any static host) and Laravel needs PHP+MySQL hosting. Fine to defer.
3. **PHP/Node locally** — confirm PHP 8.2+, Composer, and Node 18+ are installed (I can add a `docs/SETUP.md` with exact steps).
4. **GPS on delivery** — caps says "if a mobile app is utilized, GPS; otherwise manual." Plan builds the web SPA first with **manual location + QR**; native GPS is a later mobile step (Sanctum tokens already support it).

---

## Next step

On your go-ahead, I'll scaffold both repos (Laravel API + React SPA), commit the folder structure + `docs/ARCHITECTURE.md` + module template, then build **M1 (Foundation: Database & API Layer + Auth/RBAC)** with its module docs.
