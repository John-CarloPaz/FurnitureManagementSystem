# Order Management / Fulfillment FSM

Quotation module #4. A customer places a **multi-item** order against published products; it moves through a **role-owned** manufacturing/delivery FSM.

## Purpose
Manage the order lifecycle as a strict, audited, role-owned state machine — the real-world production simulation.

## Responsibilities
- Multi-item order placement (line items snapshot product name + price) + **basic pricing** (totals) + **payments** (audit log).
- The fulfillment FSM: valid transitions, role ownership, data guards, audit trail, event.
- It does **not** own the 3D model (that's the Product) or manufacturing stage records (Manufacturing module).

## FSM (State pattern + role ownership)
- `Domain/Orders/States/` — `OrderStateContract` + `AbstractOrderState` + 10 state classes (PLACED…COMPLETED + REWORK/CANCELLED); `OrderStateFactory`.
- `TransitionOwnership` — `(from→to) → allowed roles` map. **Admin** may do any transition; staff act on any order for the steps their role owns; customers only on their own order (place/cancel). Full table: [FSM.md §2](../design/FSM.md).
- `GuardRegistry` — data guards (e.g. all items finished → QC), registered by owning modules.
- `TransitionOrderAction` — the single mutation path: graph check → guards → persist + audit + milestone timestamps + `OrderTransitioned` event, atomically.
- `InvalidTransitionException` → `422`; wrong role → `403` (via `OrderPolicy`).

## Public API
| Method | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/orders` | policy `viewAny` | list (customers → own) |
| POST | `/api/v1/orders` | policy `place` | place multi-item order (`items[]`) |
| GET | `/api/v1/orders/{order}` | policy `view` | detail + items + payments + history |
| POST | `/api/v1/orders/{order}/transition` | policy `transition` (role-owned) | `{to, note?}` |
| GET | `/api/v1/orders/{order}/transitions` | policy `view` | audit trail |
| POST | `/api/v1/orders/{order}/payments` | policy `recordPayment` (admin) | record a payment |

## Domain classes
| Class | Responsibility |
|---|---|
| `Actions\PlaceOrderAction` | validate published items, snapshot prices, create order + items + audit |
| `Actions\TransitionOrderAction` | guarded, atomic, role-agnostic state change (+ timestamps + event) |
| `Actions\RecordPaymentAction` | append payment, recompute `amount_paid` + `payment_status` |
| `States\TransitionOwnership` | role-per-transition map |
| `Policies\OrderPolicy` | viewAny/view/place/recordPayment + role-owned `transition` |

## Events
- **Emitted:** `OrderTransitioned` (order, from, to, actor).
- **Consumed:** none yet (Notifications/Analytics subscribe in M4/M5).

## Tests
`OrderFulfillmentTest`: multi-item placement + totals; role-owned transitions end-to-end (admin confirm → PM produce → QA pass → delivery deliver); wrong role → 403; illegal transition → 422; customer cancels own PLACED; admin payments → PARTIAL/PAID.
