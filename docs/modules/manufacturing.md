# Manufacturing Tracking & Work Orders

Quotation modules #2 (Manufacturing Tracking) + #10 (Production Scheduling / Work Orders). Tracks each **ordered item** through the shop floor while its order is in production.

## Purpose
Give the shop floor per-item stage tracking (cutting → assembly → sanding → finishing → QC), delay detection, role-owned QC, and a live dashboard — the real-world production simulation.

## Responsibilities
- Seed & track **manufacturing stages** per `order_item`.
- Enforce the **QC guards** on the order FSM (finish before QC; pass QC before delivery).
- Assign operatives to items via **work orders**.
- Broadcast stage changes for the live shop-floor view.
- It does **not** own the order FSM (Orders) — it *guards* two of its transitions.

## Stages
Each item gets 5 stages on entering production: `cutting, assembly, sanding, finishing, qc` (`StageType`). Statuses: `pending → in_progress → done` (+ `blocked`). Seeding is automatic and idempotent — a listener on `OrderTransitioned → IN_PRODUCTION` runs `InitializeProductionAction` (rework doesn't duplicate stages).

**Delay detection:** on completion, a stage is flagged `is_delayed` if its duration exceeded `expected_minutes` (defaults per stage in `StageType`).

## FSM guards (registered by `ManufacturingServiceProvider`)
- `IN_PRODUCTION → QUALITY_CHECK` — all items' **Finishing** done.
- `QUALITY_CHECK → READY_FOR_DELIVERY` — all items' **QC** done + passed.

## Public API
| Method | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/shop-floor` | `manufacturing.view` | live board: orders in production/QC/rework with item progress + delays |
| GET | `/api/v1/orders/{order}/production` | `manufacturing.view` | production detail for one order |
| POST | `/api/v1/order-items/{item}/stages/{stage}/start` | per-stage¹ | start a stage |
| POST | `/api/v1/order-items/{item}/stages/{stage}/complete` | per-stage¹ | complete (+`qc_passed` for QC); delay computed |
| POST | `/api/v1/order-items/{item}/stages/{stage}/flag` | per-stage¹ | mark blocked with a note |
| GET/POST | `/api/v1/work-orders` · PATCH `/work-orders/{id}` | `manufacturing.view` / `workorders.assign` | assign operatives to items |

¹ **Per-stage authorization:** production stages require `manufacturing.stage.update` (operatives + PM); the **QC** stage requires `manufacturing.verify` (QA + PM). Enforced in the controller by stage type.

## Real-time
`ManufacturingStageUpdated implements ShouldBroadcast` on `private-shop-floor`. Pushes over Pusher once `BROADCAST_CONNECTION=pusher`; until then it no-ops on the `log` driver and the SPA shop-floor polls.

## Domain classes
| Class | Responsibility |
|---|---|
| `Enums\StageType` / `StageStatus` | stage catalog + expected durations |
| `Actions\InitializeProductionAction` | seed 5 stages per item |
| `Actions\StartStageAction` / `CompleteStageAction` | stage lifecycle + delay + broadcast |
| `Support\ProductionProgress` | aggregations for guards + `percent` |
| `Models\ManufacturingStage` / `WorkOrder` | per-item stage + assignment |

## Tests
`ManufacturingTest`: stages seed on production start; QC blocked before finishing (422); full operative→PM→QA flow; operative can't run QC (403); shop-floor lists in-production orders; work-order assignment + operative scoping. 28 suite tests green; Larastan L6 clean.
