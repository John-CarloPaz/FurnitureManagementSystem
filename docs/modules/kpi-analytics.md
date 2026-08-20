# KPI & Analytics Dashboard

Quotation module #6, caps §KPI Dashboard. Live operational metrics for managers.

## Purpose
Turn the data every module produces (orders, manufacturing stages, deliveries) into decision-ready KPIs — no manual reports.

## Headline KPIs
| KPI | Definition |
|---|---|
| **OTE** | Simplified: performance (Σ expected ÷ Σ actual stage minutes, capped) × quality (1 − defect rate). Availability assumed 100%. |
| **Avg Lead Time** | Mean days from `placed_at` → `delivered_at` over delivered orders. |
| **On-Time Rate** | % of delivered orders delivered within the target lead time (`config/analytics.php`, default 14 days). |
| **Defect / Rework Rate** | % of QC checks that failed (`manufacturing_stages` where `stage=qc`). |

Plus **orders by status**, **deliveries by status**, and the **production bottleneck** (stage with the most delays + per-stage average duration).

## API (role-scoped)
| Method | Route | Permission | Returns |
|---|---|---|---|
| GET | `/api/v1/kpi` | `kpi.view` (admin/QA) | headline + orders/deliveries by status + bottleneck |
| GET | `/api/v1/kpi/shop-floor` | `kpi.view.shopfloor` (Prod. Mgr) | OTE, defect rate, bottleneck |
| GET | `/api/v1/kpi/delivery` | `kpi.view.delivery` (Logistics) | on-time rate, avg lead time, deliveries by status |

## Implementation
`Domain\Analytics\Support\KpiService` — read-only Postgres aggregates (`FILTER`, `EXTRACT(EPOCH …)`). No cached snapshots yet; the `kpi_snapshots` table is reserved for periodic captures later.

## Frontend
`AnalyticsPage` fetches the richest endpoint the role can see (`useKpi`), showing KPI cards, an orders-by-status breakdown, and a bottleneck chart. Refreshes every 60s.

## Tests
`AnalyticsTest`: computed lead time + on-time rate from seeded orders; role scoping (Prod Mgr → shop-floor only, Logistics → delivery only). Larastan level 6 clean.
