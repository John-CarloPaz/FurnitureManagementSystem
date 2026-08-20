# Roadmap & Task Tracker — Cedarside Holding Corp.

Single-tenant furniture company: **Catalog** (publish products) → **Orders** (customers order published products, multi-item) → **Manufacturing** (role-owned production) → **Delivery**. Ordered, dependency-driven.

Legend: `[ ]` todo · `[~]` in progress · `[x]` done

> **2026-07-07 — Domain realignment.** The earlier "Order" (M2) conflated *product listing* with *order fulfillment*. Corrected into two lifecycles: a **Product catalog** (draft/publish, owns the 3D model) and an **Order fulfillment** FSM (role-owned steps). See [DATA_MODEL](./design/DATA_MODEL.md) / [FSM](./design/FSM.md) / [RBAC](./design/RBAC.md). The M2 code (State pattern, 3D upload/versioning, R3F viewer, RBAC) is **reused**, not discarded.

---

## Phase 0 — Design Foundation ✅
- [x] ERD · FSM · Events · RBAC · API · Design system (all in `docs/design/`, **revised 2026-07-07**)

## Phase 1 — Scaffold ✅
- [x] 1.1 Laravel 12 API (Sanctum, Postgres, spatie, Larastan) · 1.2 React SPA (Warm Craft, app shell) · 1.3 docs + 3 git repos + CI

## Phase 2 — M1 Foundation ✅
- [x] 2.1 Database & API Layer · 2.2 Auth + RBAC · 2.3 Frontend auth · 2.4 Backend tests

## Phase 2.5 — M2 (original) — SUPERSEDED, reused by realignment
- [x] Order FSM (State pattern, GuardRegistry, audit, policy) — _reshaped into Order-fulfillment in R2_
- [x] 3D upload/versioning/signed URLs + R3F viewer — _moved from Order to **Product** in R1_

---

## Phase R — Domain Realignment ✅

### R1 — Catalog (Products) ✅
- [x] Migrations renumbered; `products`, `product_images`, `models_3d` moved to `product_id`
- [x] `Product` model + `ProductStatus` enum; `ChangeProductStatusAction` with **publish guard** (needs 3D + price)
- [x] 3D upload/versioning/signed-URL moved Order → **Product** (approval step removed)
- [x] `ProductController` (CRUD + publish/unpublish/archive) + `ProductPolicy`; customer catalog = published only
- [x] Frontend: catalog grid (browse + admin create), product detail w/ R3F 3D viewer + upload
- [x] `CatalogTest` + `docs/modules/products-catalog.md`

### R2 — Orders (fulfillment) ✅
- [x] Reshaped `orders` (pricing, `payment_status`), added `order_items`, `order_payments`
- [x] New `OrderState` (PLACED→…→COMPLETED + REWORK/CANCELLED) + rewritten state classes
- [x] `TransitionOwnership` (role-per-step) wired into `OrderPolicy`; blanket `orders.transition` dropped
- [x] `PlaceOrderAction` (multi-item, totals) + `RecordPaymentAction`; reworked seeder/routes
- [x] Frontend: cart → multi-item place order; order detail = fulfillment tracker (stepper, items, totals, payments, role-based transitions); nav (Catalog/Orders)
- [x] `OrderFulfillmentTest` (20 tests total green, Larastan clean) + `docs/modules/orders-fsm.md`

---

## Phase 3 — M3 Manufacturing (on order_items) ✅
- [x] 3.1 Manufacturing Tracking — per-item stages (cutting→…→qc) auto-seeded on production start, start/complete/flag, **delay detection**, per-stage-type authz, QC FSM guards, shop-floor endpoint → `docs/modules/manufacturing.md`
- [x] 3.2 Work Orders — assign operatives to items (+ scheduling fields), operative-scoped listing
- [x] 3.3 Real-time — `ManufacturingStageUpdated` broadcast (Pusher-ready) + **live shop-floor dashboard** (10s polling) + operative stage controls + production panel on order detail; 28 tests green

## Phase 4 — M4 Delivery + Comms ✅
- [x] 4.1 Delivery Tracking — assignment → dispatch (pickup) → location → proof-of-delivery; FSM guards (assignment/proof); signed proof URLs; deliveries board + driver actions → `docs/modules/delivery.md`
- [x] 4.2 Notification System — in-app (DB) + email, event-subscribed (OrderPlaced/OrderTransitioned/StageDelayed); notification bell w/ unread badge + polling → `docs/modules/notifications.md`

## Phase 5 — M5 Insight + Intelligence ✅
- [x] 5.1 KPI & Analytics — OTE, avg lead time, on-time rate, defect/rework, orders-by-status, bottleneck; role-scoped endpoints + adaptive dashboard → `docs/modules/kpi-analytics.md`
- [x] 5.2 Model-Driven DSS — scheduling (`SchedulingStrategy`), bottleneck (`WorkflowAnalyzer`), routing (`RouteOptimizer`) behind interfaces; DSS panel on Analytics → `docs/modules/dss-engine.md`

## Phase 6 — M6 Hardening ✅
- [x] 6.1 Frontend unit tests — Vitest + Testing Library (8 tests) wired into CI; API-level golden-path covered by 43 feature tests. _(Browser E2E via Playwright noted as a future add in [TESTING.md](./TESTING.md).)_
- [x] 6.2 Security — removed a committed build-cache + hardcoded test password; `.env`/`build` ignored; **RA 10173 checklist** → `docs/SECURITY.md`
- [x] 6.3 `docs/SETUP.md`, `docs/DEPLOYMENT.md`, `docs/UAT-CHECKLIST.md`
- [x] 6.4 Docs index updated with all 10 module docs + guides
