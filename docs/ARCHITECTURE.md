# Architecture Overview — Cedarside Holding Corp.

System-level map. For deep dives see the design docs linked below and the per-module docs in [`modules/`](./modules/).

---

## 1. What it is

A web-based real-time monitoring system for furniture manufacturing & delivery, with 3D model viewing, an order-lifecycle FSM, role-based access, live KPIs, and a Model-Driven DSS. Two standalone apps talking over a REST API.

```
┌─────────────────────┐         HTTPS / JSON          ┌──────────────────────┐
│   React SPA          │  ───────────────────────────▶ │   Laravel API (v1)   │
│   (frontend/)        │      Bearer token (Sanctum)   │   (backend/)         │
│                      │ ◀───────────────────────────  │                      │
│  Vite · TS · Tailwind│                                │  Domain modules      │
│  R3F 3D · TanStack   │      Pusher (WebSockets)       │  FSM · Events · RBAC │
│  Echo/pusher-js      │ ◀──────── private channels ─── │  Broadcasting        │
└─────────────────────┘                                └──────────┬───────────┘
                                                                   │
                                                        ┌──────────▼───────────┐
                                                        │   PostgreSQL 16      │
                                                        │  furniture_monitoring│
                                                        └──────────────────────┘
```

## 2. Repos

| Repo | Stack | Notes |
|---|---|---|
| `backend/` | Laravel 12, PHP 8.2, PostgreSQL, Sanctum, spatie/permission, Pusher | API-only; domain-module architecture |
| `frontend/` | React 18, Vite, TS, Tailwind v4, react-three-fiber, TanStack Query | Standalone SPA; Warm Craft design system |
| `docs/` | Markdown (this repo) | Design foundation + roadmap + module docs |

## 3. Backend structure (SOLID, domain-oriented)

```
backend/app/
├── Domain/<Module>/     Actions · Services · Contracts · Repositories · Models · DTOs · Events · States
├── Http/                Controllers (thin) · Requests · Resources · Middleware
└── Providers/           bind Contracts → implementations
```

Principles and their concrete expression are in [`../PLAN.md`](../PLAN.md) §3. In short: thin controllers → single-purpose Actions → repository interfaces; the FSM is a State pattern; DSS algorithms sit behind strategy interfaces; modules communicate only via **events**.

## 4. The five design pillars

| Pillar | Doc | Role |
|---|---|---|
| Data model | [DATA_MODEL.md](./design/DATA_MODEL.md) | Postgres schema (DBML + ERD) |
| Order lifecycle | [FSM.md](./design/FSM.md) | State machine: states, transitions, guards, audit |
| Eventing | [EVENTS.md](./design/EVENTS.md) | Domain events → listeners; broadcast channels |
| Access control | [RBAC.md](./design/RBAC.md) | 7 roles × permissions; 3 enforcement layers |
| API | [API.md](./design/API.md) | Endpoint contract per module |
| Visual | [design/DESIGN_SYSTEM.md](./design/DESIGN_SYSTEM.md) | Warm Craft tokens & components |

## 5. Request → effect flow (example: customer approves a 3D model)

1. `POST /api/v1/orders/{id}/approve` (Sanctum-authenticated customer).
2. `OrderPolicy@approve` checks ownership + permission.
3. `ApproveModelAction` runs the FSM guard, transitions `PENDING_APPROVAL → APPROVED`, writes `order_state_transitions`, dispatches `OrderApproved` — all in one DB transaction.
4. Queued listeners: Notifications alerts the production manager; Analytics stamps approval time.
5. `OrderApproved` broadcasts on `private-order.{id}`; the customer's SPA updates live (TanStack Query cache patched), with a polling fallback.

## 6. Environments

- **Dev:** Laravel `php artisan serve` (:8000); Vite dev server (:5173, proxies `/api`). Broadcasting = `log` until Pusher keys added. Mail = `log`.
- **Prod (later):** static SPA build on any host; Laravel on PHP+Postgres host; S3 for 3D files; Pusher for realtime. Client handles hosting (per quotation).

## 7. Build order

See [ROADMAP.md](./ROADMAP.md). Foundation (DB+API, Auth/RBAC) → Order FSM + 3D → Production → Delivery + Notifications → KPIs + DSS → Hardening.
