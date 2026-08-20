# UAT Sign-off Checklist

Manual acceptance test of the golden path. Create one user per role (Users page, admin) before starting. Tick each; note defects.

## Setup
- [ ] Seeded admin can log in (`admin@timbr.local`)
- [ ] Admin creates users: production manager, operative, QA tester, logistics coordinator, delivery personnel, customer
- [ ] Sidebar shows only the modules each role may access

## Catalog (Admin)
- [ ] Create a product with category / material / wood type / finish / dimensions / price
- [ ] Upload a `.glb` — it renders in the 3D viewer (rotate + zoom)
- [ ] **Publish is blocked** until a 3D model + price exist; then publish succeeds
- [ ] Edit a product; changes persist

## Order (Customer)
- [ ] Customer sees only **published** products
- [ ] Add products to cart, place a **multi-item** order → status **PLACED**; total correct
- [ ] Customer receives an in-app notification; admin gets a "new order" notification

## Fulfillment (each role owns its step)
- [ ] Admin **confirms** (PLACED → CONFIRMED); customer cannot confirm
- [ ] Production Manager **starts production** → stages seeded per item
- [ ] Operative updates stages (cutting → … → finishing); a delayed stage flags red + alerts the manager
- [ ] Production Manager submits to **QC**; QA **passes** (or fails → REWORK)
- [ ] QA pass → **READY_FOR_DELIVERY**

## Delivery
- [ ] Logistics **assigns** the ready order to a driver
- [ ] Driver **marks picked up** → OUT_FOR_DELIVERY; logs a location
- [ ] Driver **uploads proof** (photo + recipient) → **DELIVERED**; customer notified
- [ ] Admin **completes** the order → COMPLETED
- [ ] Order history shows every transition with actor + timestamp

## Insight
- [ ] KPI dashboard shows On-Time %, Avg Lead Time, OTE, Defect/Rework, orders-by-status, bottleneck
- [ ] Production Manager sees the **DSS** panel: bottleneck recommendation + suggested production order
- [ ] KPI endpoints are role-scoped (manager → shop-floor, logistics → delivery)

## Cross-cutting
- [ ] Notification bell shows unread count; mark-all-read works
- [ ] A customer cannot view another customer's order (403)
- [ ] Light/dark theme + ⌘K command palette work

**Signed off by:** ____________________  **Date:** __________
