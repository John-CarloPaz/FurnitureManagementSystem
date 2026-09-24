# Module — Audit Log

An immutable trail of **who edited what**: every authenticated create/update/delete on a business entity is recorded with the actor, the HTTP method, the entity, and the **field-level diff**.

## Design
- **One global observer, no model edits.** [`AuditServiceProvider`](../../backend/app/Domain/Audit/AuditServiceProvider.php) attaches [`AuditObserver`](../../backend/app/Domain/Audit/AuditObserver.php) to a curated list of models (Product, Order, OrderItem, User, ManufacturingStage, WorkOrder, QualityInspection, DeliveryAssignment, ProofOfDelivery, ModelGeneration, Model3DVersion, Invitation). Adding a model to the trail is one line in that list.
- **Authenticated writes only.** [`AuditRecorder`](../../backend/app/Domain/Audit/AuditRecorder.php) skips when there's no logged-in user, so seeders, queue jobs, and public sign-up produce no noise — every row is a real user action.
- **Field-level diff.** On `updated` it records `{field: {old, new}}` from the model's dirty attributes; on `created`, the set values; on `deleted`, just the event. Timestamps are ignored, and sensitive fields (`password`, `*token*`, `*secret*`, `phone`, `address`) have their **values masked** (`•••`) while still showing that they changed — keeping PII out of the log per RA 10173.
- **Request context.** Each row also captures the HTTP `method`, request `path`, and `ip_address`, plus a snapshot of the actor's name (survives user deletion). The insert runs inside the request's transaction, so a rolled-back change rolls back its audit row too.

## Data
`audit_logs`: `user_id` + `user_name` (who) · `event` (created/updated/deleted) · `method` (POST/PATCH/DELETE) · `path` · `auditable_type` + `auditable_id` (what) · `changes` (JSON diff) · `ip_address` · `created_at` (append-only, no `updated_at`).

## API & UI
| Method | Path | Guard |
|---|---|---|
| GET | `/api/v1/audit-logs?page=&event=&entity=&user_id=` | `audit.view` |

Frontend: `/audit` (`pages/audit-page.tsx`), sidebar **Audit Log** (shown to holders of `audit.view` — admin, super_admin, qa_tester). Table: When · Who · Method · Entity + event · Fields changed (rendered `field: old → new`), with an event filter and pagination.

## Tests
`backend/tests/Feature/AuditLogTest.php` — a write logs actor + method + changed field; the endpoint is permission-gated; seeders/unauthenticated changes are not logged; sensitive fields are excluded/redacted.
