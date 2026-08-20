# Cedarside Holding Corp. — Documentation

Web-Based Real-Time Monitoring System with 3D Model Integration for Furniture Product Manufacturing and Delivery.

## Start here
- **[PLAN.md](./PLAN.md)** — full project plan: decisions, tech stack, SOLID architecture, milestones
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** — system overview & request flow
- **[ROADMAP.md](./ROADMAP.md)** — ordered task tracker (progress lives here)
- **[SETUP.md](./SETUP.md)** — run it locally · **[TESTING.md](./TESTING.md)** — test coverage
- **[SECURITY.md](./SECURITY.md)** — security + RA 10173 · **[DEPLOYMENT.md](./DEPLOYMENT.md)** — deploy · **[UAT-CHECKLIST.md](./UAT-CHECKLIST.md)** — sign-off

## Design foundation (`design/`)
- **[DATA_MODEL.md](./design/DATA_MODEL.md)** — Postgres schema (DBML + Mermaid ERD)
- **[FSM.md](./design/FSM.md)** — order lifecycle state machine
- **[EVENTS.md](./design/EVENTS.md)** — domain events & listeners
- **[RBAC.md](./design/RBAC.md)** — 7 roles × permissions matrix
- **[API.md](./design/API.md)** — REST endpoint contract
- **[DESIGN_CONCEPT.md](./design/DESIGN_CONCEPT.md)** — UX principles & signature screens
- **[DESIGN_SYSTEM.md](./design/DESIGN_SYSTEM.md)** + **[tokens.css](./design/tokens.css)** — Warm Craft visual system

## Modules (`modules/`)
One doc per module, written alongside its code (template: [_template.md](./modules/_template.md)).
- [database-api.md](./modules/database-api.md) · [auth-rbac.md](./modules/auth-rbac.md)
- [products-catalog.md](./modules/products-catalog.md) · [3d-viewer.md](./modules/3d-viewer.md)
- [orders-fsm.md](./modules/orders-fsm.md) · [manufacturing.md](./modules/manufacturing.md)
- [delivery.md](./modules/delivery.md) · [notifications.md](./modules/notifications.md)
- [kpi-analytics.md](./modules/kpi-analytics.md) · [dss-engine.md](./modules/dss-engine.md)

## Repos
- `backend/` — Laravel 12 API (separate git repo)
- `frontend/` — React SPA (separate git repo)
- `docs/` — this repo
