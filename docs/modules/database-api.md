# Database & API Layer

Quotation module #9. The foundation every other module builds on.

## Purpose
Provide the relational schema, the archival strategy, and the shared API conventions (envelope, errors, health) for the whole system. Maps to caps §1.4 (data management) and §Database & API Layer.

## Responsibilities
- Own the **core schema** (all tables from [DATA_MODEL](../design/DATA_MODEL.md)).
- Provide the **orders archival** mechanism (`orders_archive` + scheduled move).
- Define **API-wide conventions**: `/api/v1` prefix, JSON error envelope, health probe.
- It does **not** own business logic — that lives in each domain module.

## Schema
Created by 5 domain-grouped migrations (`database/migrations/2026_07_06_*`):

| Migration | Tables |
|---|---|
| `..._create_orders_tables` | `orders`, `orders_archive`, `order_state_transitions` |
| `..._create_model_tables` | `models_3d`, `model_3d_versions` (+ deferred current-version FK) |
| `..._create_manufacturing_tables` | `suppliers`, `wood_batches`, `order_wood_batches`, `production_schedules`, `work_orders`, `manufacturing_stages` |
| `..._create_delivery_tables` | `delivery_assignments`, `delivery_events`, `proof_of_deliveries` |
| `..._create_analytics_notifications_tables` | `kpi_snapshots`, `notifications` |

Plus framework tables (`users` + encrypted PII columns, Sanctum, spatie permission). All use `timestamptz`. The circular pointer FK (`models_3d.current_version_id`) is added after both tables exist.

## Archival
`php artisan orders:archive {--months=12}` moves terminal orders (`CLOSED`/`CANCELLED`) older than the threshold into `orders_archive` (single `INSERT … SELECT` + delete, in a transaction). Scheduled monthly (`routes/console.php`). Chosen over live partitioning to keep FKs clean — see [DATA_MODEL](../design/DATA_MODEL.md) design notes.

> Caveat: `order_state_transitions` cascade-delete with the order. Extend the command to archive transitions too if long-term audit retention is required.

## API conventions
- **Base:** all routes under `/api/v1` (`routes/api.php`).
- **Success envelope:** `{ "data": … , "meta": … }` (via API Resources).
- **Error envelope:** `{ "message": …, "errors": {…} }` — forced JSON for `api/*` via `bootstrap/app.php` `shouldRenderJsonWhen`. Full trace only in `APP_DEBUG=true`.
- **Health:** `GET /api/v1/health` → `{ data: { status, service, database } }`, `200` healthy / `503` degraded.

## Domain classes
| Class | Type | Responsibility |
|---|---|---|
| `App\Domain\Orders\Enums\OrderState` | Enum | canonical FSM value set (+ `label()`, `isTerminal()`) |
| `App\Domain\Orders\Models\Order` | Model | order spine; casts `state` → `OrderState` |
| `App\Domain\Orders\Models\OrderStateTransition` | Model | append-only audit row |
| `App\Console\Commands\ArchiveOldOrders` | Command | the archival job |

## Tests
Covered in M1 task 2.4: migration integrity, `orders:archive` move behavior, health endpoint, JSON error envelope.
Verified manually: all migrations green on Postgres, `orders:archive` runs, `/api/v1/health` returns healthy, Larastan level 6 clean.
