# RBAC — Roles & Permissions

8 system roles (single company) **plus super-admin-defined custom roles**. `spatie/laravel-permission`. Enforced via route middleware, **Policies**, and broadcast-channel auth. Least privilege.

The permission set is defined once in [`app/Domain/Access/PermissionCatalog.php`](../../backend/app/Domain/Access/PermissionCatalog.php) — the seeder, the `GET /permissions` matrix, and the super-admin **role builder** all read from it, so UI and enforcement can't drift. See [modules/user-management.md](../modules/user-management.md).

Two authorization styles:
- **Permission-based** for CRUD/module access (below).
- **Role-owned transitions** for the order FSM — each fulfillment step is gated to the role that performs it (a `(from,to) → roles` map in `OrderPolicy`), simulating real production hand-offs.

---

## Roles

| # | Role (slug) | Scope |
|---|---|---|
| 0 | `super_admin` | Everything, **plus building/editing roles**. Seeded from `ADMIN_*` env. Only role that can create roles + grant the privileged roles |
| 1 | `admin` | Everything except role mutation: catalog, users, invitations, KPIs, audit; confirms & completes orders |
| 2 | `production_manager` | Catalog view; starts/manages production; verifies stages; shop-floor KPIs |
| 3 | `manufacturing_operative` | Updates production stages on assigned items |
| 4 | `logistics_coordinator` | Assigns deliveries, dispatches, on-time KPIs |
| 5 | `delivery_personnel` | Marks out-for-delivery / delivered; proof of delivery |
| 6 | `customer` | Browses published catalog, places & tracks own orders |
| 7 | `qa_tester` | QC pass/fail; read-only audit across modules |

---

## Permissions (by module)

| Module | Permissions |
|---|---|
| Users | `users.create` · `users.view` · `users.update` · `users.delete` |
| Roles | `roles.viewAny` · `roles.create` · `roles.update` · `roles.delete` *(mutation = super_admin only)* |
| Invitations | `invitations.viewAny` · `invitations.create` · `invitations.revoke` |
| **Catalog** | `products.create` · `products.viewAny` (incl. drafts / "Read") · `products.update` · `products.delete` · `products.publish` · `products.browse` (published only) |
| Orders | `orders.viewAny` · `orders.view.own` · `orders.place` · `orders.cancel` |
| Manufacturing | `manufacturing.view` · `manufacturing.stage.update` · `manufacturing.verify` · `manufacturing.schedule` · `workorders.assign` |
| Delivery | `delivery.view` · `delivery.assign` · `delivery.update` · `delivery.proof.upload` |
| Notifications | `notifications.view.own` |
| Analytics | `kpi.view` · `kpi.view.shopfloor` · `kpi.view.delivery` |
| Audit | `audit.view` · `reports.run` |

> Order **fulfillment transitions** are not permissions — they are role-owned (next table).

---

## Order transition ownership (role-per-step)

| Transition | Owning role(s) |
|---|---|
| → PLACED | `customer` (own) |
| PLACED → CONFIRMED | `admin` |
| PLACED / CONFIRMED → CANCELLED | `admin`; `customer` (own, pre-production) |
| CONFIRMED → IN_PRODUCTION | `production_manager` |
| IN_PRODUCTION → QUALITY_CHECK | `production_manager` |
| QUALITY_CHECK → READY_FOR_DELIVERY / REWORK | `qa_tester` |
| REWORK → IN_PRODUCTION | `production_manager` |
| READY_FOR_DELIVERY → OUT_FOR_DELIVERY | `logistics_coordinator`, `delivery_personnel` |
| OUT_FOR_DELIVERY → DELIVERED | `delivery_personnel` |
| DELIVERED → COMPLETED | `admin` |

`admin` may perform any transition (override). Manufacturing **stage** updates (cutting→…) are separate and use `manufacturing.stage.update` (operatives).

---

## Role × permission matrix

`●` full · `○` own/assigned · `R` read-only · blank none

| Permission | admin | prod_mgr | operative | logistics | delivery | customer | qa |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| users.manage / roles.manage | ● | | | | | | |
| products.viewAny | ● | ● | ● | | | | R |
| products.browse (published) | ● | ● | ● | ● | ● | ● | ● |
| products.manage / publish | ● | | | | | | |
| orders.viewAny | ● | ● | ○ | ● | ○ | | R |
| orders.view.own | ● | | | | | ● | |
| orders.place | ● | | | | | ● | |
| orders.cancel | ● | | | | | ○ | |
| manufacturing.view | ● | ● | ● | | | | R |
| manufacturing.stage.update | ● | ● | ○ | | | | |
| manufacturing.verify / schedule / workorders.assign | ● | ● | | | | | |
| delivery.view | ● | | | ● | ○ | | R |
| delivery.assign | ● | | | ● | | | |
| delivery.update / proof.upload | ● | | | | ○ | | |
| kpi.view (all) | ● | | | | | | R |
| kpi.view.shopfloor | ● | ● | | | | | R |
| kpi.view.delivery | ● | | | ● | | | R |
| audit.view / reports.run | ● | | | | | | ● |
| notifications.view.own | ● | ● | ● | ● | ● | ● | ● |

---

## Enforcement
1. **Middleware** — `auth:sanctum` + `permission:*` on CRUD routes.
2. **Policies** — `ProductPolicy` (manage/publish/browse), `OrderPolicy` (view/place/cancel + the transition-ownership map). Registered via `Gate::policy` (domain models).
3. **Channel auth** — mirrors these checks for Pusher private channels.
4. **Resources** — role-shaped output (customers never see cost/operator internals).

## Seeding
`RolePermissionSeeder` creates all permissions + roles + the matrix, and a default admin from `ADMIN_*` env.
