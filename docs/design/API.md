# API Contract (v1)

Laravel API-only. The React SPA codes against this contract; it's frozen enough to build the frontend in parallel with the backend. Full OpenAPI spec is generated during M1; this is the human-readable reference.

---

## Conventions

- **Base URL:** `/api/v1`
- **Auth:** Sanctum bearer token — `Authorization: Bearer <token>`. Obtain via `POST /auth/login`.
- **Success envelope:** `{ "data": <resource|array>, "meta": {...pagination} }`
- **Error envelope:** `{ "message": "...", "errors": { "field": ["..."] } }` — validation `422`, authz `403`, auth `401`, not found `404`, illegal FSM transition `422`.
- **Pagination:** `?page=`, `?per_page=` → `meta: { current_page, last_page, total }`.
- **Filtering/sorting:** `?filter[state]=IN_PRODUCTION&sort=-created_at`.
- **Output is role-shaped** via API Resources (customers never see cost/operator internals).
- **Idempotency:** transition + upload endpoints reject duplicate/illegal actions (state guards).

---

## Auth
| Method | Path | Roles | Body / notes |
|---|---|---|---|
| POST | `/auth/login` | public | `{ email, password }` → `{ token, user }` |
| POST | `/auth/logout` | any | revokes current token |
| GET | `/auth/me` | any | current user + roles + permissions |

## Users & Roles
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/users` | admin, (dept-scoped) prod_mgr/logistics | list/filter |
| POST | `/users` | admin, prod_mgr¹ | create user, assign role |
| GET | `/users/{id}` | admin, self | |
| PATCH | `/users/{id}` | admin, self (limited) | |
| DELETE | `/users/{id}` | admin | soft delete / deactivate |
| GET | `/roles` | admin | roles + permissions catalog |

## Orders (FSM spine)
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/orders` | all (scoped) | customer → own; operative/delivery → assigned |
| POST | `/orders` | admin, prod_mgr, customer¹ | creates in `DRAFT` |
| GET | `/orders/{id}` | scoped | full detail incl. current state + model |
| PATCH | `/orders/{id}` | admin, prod_mgr | edit fields (not state) |
| POST | `/orders/{id}/transition` | scoped per FSM | `{ to, note? }` → guarded; writes audit; emits event |
| GET | `/orders/{id}/transitions` | staff, qa (R) | audit trail |

## 3D Models
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/orders/{id}/model-versions` | scoped | version history + change logs |
| POST | `/orders/{id}/model-versions` | admin, prod_mgr, customer¹ | multipart `.glb`/`.obj`; creates `models_3d` if absent; ≤ configured max size |
| GET | `/model-versions/{id}/download` | scoped | returns **signed URL** (1h expiry) |
| POST | `/orders/{id}/approve` | **customer** (own) | approves current version → FSM `APPROVED` |

## Manufacturing & Scheduling
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/orders/{id}/stages` | staff, qa (R) | stage list + progress + delay flags |
| POST | `/orders/{id}/stages/{stage}/start` | prod_mgr, operative¹ | timestamps start; broadcasts |
| POST | `/orders/{id}/stages/{stage}/complete` | prod_mgr, operative¹ | timestamps end; delay check; may emit `OrderReadyForQc` |
| POST | `/orders/{id}/stages/{stage}/flag` | prod_mgr, operative | flag QC issue |
| GET | `/shop-floor` | prod_mgr, operative, admin | live aggregate for dashboard/TV |
| POST | `/production/schedule` | prod_mgr, admin | runs DSS scheduling → work orders |
| GET | `/work-orders` | prod_mgr, operative (assigned) | |
| PATCH | `/work-orders/{id}` | prod_mgr | reassign / reschedule |

## Delivery
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/deliveries` | logistics, delivery(assigned), admin | |
| POST | `/orders/{id}/delivery` | logistics, admin | create assignment (+batch label) |
| PATCH | `/deliveries/{id}` | logistics | reassign driver / batch |
| POST | `/deliveries/{id}/events` | delivery, logistics | `{ type, lat?, lng?, manual_location? }` (QR pickup / out / location) |
| POST | `/deliveries/{id}/proof` | delivery | multipart photo + signature + recipient → FSM `DELIVERED` → delivery email |
| GET | `/deliveries/{id}` | scoped | incl. events + proof |

## Notifications
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/notifications` | any | own, paginated |
| POST | `/notifications/{id}/read` | any | mark read |
| POST | `/notifications/read-all` | any | |

## Analytics (KPIs)
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/kpi` | admin, qa(R) | OTE, avg lead time, on-time rate, defect/rework |
| GET | `/kpi/shop-floor` | prod_mgr, admin | bottleneck callouts, stage timings |
| GET | `/kpi/delivery` | logistics, admin | on-time delivery rate |

## DSS Engine
| Method | Path | Roles | Notes |
|---|---|---|---|
| POST | `/dss/schedule` | prod_mgr, admin | production sequencing (SchedulingStrategy) |
| GET | `/dss/bottlenecks` | prod_mgr, admin | WorkflowAnalyzer output |
| POST | `/dss/route-optimize` | logistics, admin | RouteOptimizer for a delivery batch |

## Audit / Reports
| Method | Path | Roles | Notes |
|---|---|---|---|
| GET | `/audit/orders/{id}` | admin, qa | full transition + action history |
| GET | `/reports/{type}` | admin, qa | UAT / compliance exports |

---

## Broadcast channels (Pusher)
| Channel | Auth (who may subscribe) |
|---|---|
| `private-order.{orderId}` | order's customer + assigned staff |
| `private-shop-floor` | prod_mgr, operative, admin |
| `private-deliveries` | logistics, admin |

¹ ownership/assignment/department constraint enforced in Policy — see [RBAC.md](./RBAC.md).
