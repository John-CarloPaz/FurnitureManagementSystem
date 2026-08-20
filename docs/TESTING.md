# Testing

## Backend — 43 feature tests (PHPUnit, on a real Postgres test DB)
| Suite | Covers |
|---|---|
| `HealthTest` | health endpoint + DB connectivity |
| `AuthTest` | login/token, wrong password, inactive account, `/me`, logout |
| `RbacTest` | permission-gated routes (200 / 403 / 401) |
| `CatalogTest` | product create, publish guard (needs 3D + price), customer sees published only, detailed fields + dropdown validation |
| `OrderFulfillmentTest` | multi-item placement + totals, role-owned transitions, illegal transition (422), payments |
| `Model3DTest`* | upload/versioning + approval (now on products) |
| `DeliveryTest` | assign → dispatch → proof; FSM guards; wrong-driver 403 |
| `NotificationTest` | order-placed/transition notifications, list + mark-read |
| `AnalyticsTest` | computed KPIs + role scoping |
| `DssTest` | EDD scheduling, nearest-neighbour routing, bottleneck, authz |
| `OrderArchiveTest` | archive move job |

Run: `cd backend && php artisan test`. Static analysis: **Larastan level 6, zero errors**. Format: Pint.

## Frontend — Vitest unit tests
`cn` util, status metadata + `peso` formatting, `StatusPill` rendering. Run: `cd frontend && npm run test`. Plus `npm run build` (type-check) and `npm run lint`.

## Golden-path coverage
The end-to-end journey — **publish → order → confirm → produce → QC → deliver → complete**, with notifications and KPIs — is verified at the **API layer** by the feature suites above (each hand-off is a tested transition with its role check and guard) and at the **component layer** by Vitest.

## Recommended next step
A browser **E2E** (Playwright) driving the real UI through the golden path would add UI-regression safety on top of the API/component coverage. Until then, use the [UAT checklist](./UAT-CHECKLIST.md) for manual sign-off.
