# Model-Driven DSS Engine

Quotation module #8, caps §Model-Driven DSS — the study's flagship. Decision support via **pluggable algorithms behind interfaces** (Open/Closed + Dependency Inversion).

## Purpose
Automate operational decisions with math/logic instead of manual judgement: what to produce next, where the workflow is stuck, and how to route deliveries.

## Three model-driven concerns
| Concern | Contract | Default implementation |
|---|---|---|
| Production scheduling | `Contracts\SchedulingStrategy` | `EarliestDueDateStrategy` (produce the most time-critical items first) |
| Workflow / bottleneck | `Support\WorkflowAnalyzer` | bottleneck stage + recommendation (reads KPI data) |
| Delivery routing | `Contracts\RouteOptimizer` | `NearestNeighborRouteOptimizer` (greedy, haversine distance) |

**Swapping an algorithm = a new class + one binding** in `DssServiceProvider` — no controller or route change. That's the SOLID payoff the caps calls for.

## API
| Method | Route | Permission | Returns |
|---|---|---|---|
| POST | `/api/v1/dss/schedule` | `manufacturing.schedule` | ordered production plan (sequence, due date) for confirmed orders |
| GET | `/api/v1/dss/bottlenecks` | `kpi.view.shopfloor` | bottleneck stage + recommendation |
| POST | `/api/v1/dss/route-optimize` | `delivery.assign` | `{stops}` (+ optional depot `start`) → visit order + `total_km` |

## Value objects
`ProductionJob` (order-item + due date, gets a `sequence`) and `RouteStop` (id/label/lat/lng) — pure data passed to the strategies, so the algorithms are trivially unit-testable without the DB.

## How it uses the data
- **Scheduling** derives each job's due date from `placed_at + product.lead_time_days` (fallback `config/analytics.php`).
- **Bottleneck** reuses `KpiService::bottleneck()` (per-stage delay + avg duration).
- **Routing** is a pure geometry algorithm — the client supplies stops (e.g. today's ready deliveries with coordinates).

## Frontend
The Analytics page shows a **Model-Driven DSS** panel (for `manufacturing.schedule`): the bottleneck recommendation + the suggested production order.

## Tests
`DssTest`: EDD sequences the urgent product first; nearest-neighbour orders stops `B→C→A` with positive distance; bottleneck returns a recommendation; a customer is forbidden. Larastan level 6 clean.
