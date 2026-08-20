# Delivery Tracking

Quotation module #3, caps Objective #2. The last mile: `READY_FOR_DELIVERY → OUT_FOR_DELIVERY → DELIVERED`.

## Purpose
Assign orders to drivers, track pickup + location, and capture proof of delivery — driving the order FSM's delivery transitions with real data.

## Responsibilities
- Logistics **assigns** a ready order to a driver (optional batch label).
- Driver **dispatches** (pickup) → order `OUT_FOR_DELIVERY`; logs **location** en route.
- Driver captures **proof of delivery** (photo + recipient) → order `DELIVERED`.
- Owns `delivery_assignments`, `delivery_events`, `proof_of_deliveries`.

## Flow & ownership
| Step | Endpoint | Role |
|---|---|---|
| Assign driver (order must be READY) | `POST /orders/{order}/delivery` | `delivery.assign` (Logistics/Admin) |
| Dispatch / pickup → `OUT_FOR_DELIVERY` | `POST /deliveries/{a}/dispatch` | assigned **driver** (`delivery.update`) |
| Log location | `POST /deliveries/{a}/location` | assigned driver |
| Proof of delivery → `DELIVERED` | `POST /deliveries/{a}/proof` (multipart photo) | assigned driver (`delivery.proof.upload`) |
| List / detail | `GET /deliveries`, `GET /deliveries/{a}` | scoped (drivers see own) |
| Unassigned ready orders / drivers | `GET /deliveries/unassigned`, `/deliveries/drivers` | `delivery.assign` |

## FSM guards (DeliveryServiceProvider)
- `READY_FOR_DELIVERY → OUT_FOR_DELIVERY` — requires a delivery assignment.
- `OUT_FOR_DELIVERY → DELIVERED` — requires proof of delivery.

So the bare order-transition endpoint can't skip the delivery process; the delivery endpoints are the real path (they create the events/proof, then transition atomically via `TransitionOrderAction`).

## Domain classes
| Class | Responsibility |
|---|---|
| `Actions\AssignDeliveryAction` | create/reassign the driver (order must be READY) |
| `Actions\DispatchDeliveryAction` | pickup event + `OUT_FOR_DELIVERY` |
| `Actions\LogLocationAction` | a location event |
| `Actions\RecordProofOfDeliveryAction` | store photo + `DELIVERED` |
| `Policies\DeliveryPolicy` | assign / update / recordProof (driver ownership) |
| `Events\DeliveryUpdated` | broadcast on `private-deliveries` (Pusher-ready) |

## Security & PII
- Proof photos served via **1-hour signed URLs** (`delivery-proofs.file`, session-less).
- `recipient_name` encrypted at rest (RA 10173). `delivery_address` on the order likewise.

## Tests
`DeliveryTest`: assign (READY only, else 422), driver dispatch → `OUT_FOR_DELIVERY`, wrong driver → 403, proof → `DELIVERED`, and the no-proof guard → 422. Larastan level 6 clean.
