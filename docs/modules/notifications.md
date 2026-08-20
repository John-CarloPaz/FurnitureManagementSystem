# Notification System

Quotation module #7, caps Objective #3. In-app + email alerts driven by domain events.

## Purpose
Keep customers and staff informed as orders and production progress — without any module calling Notifications directly. It **subscribes** to events emitted elsewhere.

## Channels
- **Database** (in-app) — Laravel `notifications` table, surfaced via the SPA bell.
- **Mail** — the `log`/Mailpit mailer in dev; real SMTP in prod (no code change).

Notifications are sent synchronously (they're lightweight). Add `ShouldQueue` + a queue worker in production to offload them.

## Event → notification map (NotificationsServiceProvider)
| Event (emitted by) | Listener | Recipients | Notification |
|---|---|---|---|
| `OrderPlaced` (Orders) | `NotifyOnOrderPlaced` | the customer + all admins | `OrderStatusNotification` (customer), `NewOrderNotification` (admins) |
| `OrderTransitioned` (Orders FSM) | `NotifyOnOrderTransition` | the customer | `OrderStatusNotification` (friendly per-status message) |
| `ManufacturingStageUpdated` (Manufacturing) | `NotifyOnStageDelay` | production managers | `StageDelayedNotification` (only when `is_delayed`) |

Because delivery transitions (`OUT_FOR_DELIVERY`, `DELIVERED`) run through `TransitionOrderAction`, the customer's "out for delivery" / "delivered" alerts come through `OrderTransitioned` automatically.

## Public API
| Method | Route | Description |
|---|---|---|
| GET | `/api/v1/notifications` | own notifications (paginated) + `meta.unread_count` |
| POST | `/api/v1/notifications/{id}/read` | mark one read |
| POST | `/api/v1/notifications/read-all` | mark all read |

Scoped to `$request->user()` — a user only ever sees their own.

## Frontend
- **`NotificationBell`** in the top bar: unread badge, dropdown list, "mark all read", click-to-open the related order. Polls every 30s (`refetchInterval`) — ready to switch to Pusher push later.

## Tests
`NotificationTest`: placing an order notifies customer + admins; a transition notifies the customer; list + mark-all-read drops unread to 0.
