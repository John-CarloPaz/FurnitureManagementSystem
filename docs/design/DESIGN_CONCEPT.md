# Design Concept — Visual Language & UX Principles

**Goal:** the product should feel like a **modern CRM / ops platform** (Linear, Attio, Notion, Retool-grade), not a boring CRUD admin panel. Every screen is intentional, data-forward, fast, and on-brand.

**Product name:** **Cedarside Holding Corp.**
**Visual direction:** **Warm Craft** — modern CRM structure with a furniture soul (walnut + amber accents, cream/ink surfaces, serif display headings, sleek sans UI).
**Brand positioning:** furniture manufacturing is a **craft** business serving upscale/custom clients (caps §4.4). The UI must feel **premium, trustworthy, and precise** — the digital equivalent of a well-made piece of furniture.

---

## 1. Core UX principles (apply to every screen)

1. **App shell, not pages.** Persistent left sidebar + top command bar. Content swaps without full reloads (SPA). No "PHP form page" energy anywhere.
2. **Command palette (⌘K).** Jump to any order, customer, or action instantly. Signals "serious tool."
3. **Data-dense but calm.** Tables, KPI cards, and status pills with generous whitespace and a strict type scale. Information hierarchy over decoration.
4. **Status is a first-class visual.** The FSM state (Draft → In Production → Delivered) shown everywhere as color-coded pills + a horizontal stepper on the order page.
5. **Real-time feels alive.** When a stage updates via Pusher, the row/card animates subtly (highlight fade, count tick). No manual refresh, ever.
6. **Role-tailored home.** Each of the 7 roles lands on a dashboard built for their job — not one generic screen with hidden buttons.
7. **Micro-interactions.** Hover states, skeleton loaders, optimistic updates, toast confirmations. Motion is quick (150–250ms) and purposeful.
8. **Dark + light mode** from day one (modern CRMs ship both).
9. **The 3D viewer is a hero moment.** Full-bleed canvas, floating glass controls, cinematic — this is the product's signature screen.

---

## 2. Signature screens (where "modern" must show)

| Screen | Role(s) | Design intent |
|---|---|---|
| **Order board** | Admin, Prod Mgr | Kanban-by-FSM-state OR data table toggle; drag not required, but live-updating columns |
| **Order detail** | All (scoped) | FSM stepper header, 3D viewer, timeline of transitions, stage progress bars |
| **3D viewer** | Customer, Prod | Full-bleed R3F canvas, glass control dock, version switcher, "Approve for Production" CTA |
| **Shop-floor dashboard** | Prod Mgr, Operative | Live stage cards, delay alerts glowing red, big-screen/TV mode |
| **KPI dashboard** | Admin, managers | Recharts, animated counters, trend sparklines, bottleneck callout cards |
| **Delivery board** | Logistics, Delivery | Map/route view, batch groups, proof-of-delivery gallery |

---

## 3. Layout system

- **Left sidebar** (collapsible): logo, role-scoped nav, user menu at bottom.
- **Top bar**: breadcrumb + ⌘K search + notifications bell + theme toggle.
- **Content**: max-width containers, 8pt spacing grid, card-based sections.
- **Right drawer** (contextual): quick-view an order/customer without leaving the list.
- **Responsive**: sidebar → bottom nav on mobile; delivery personnel screens are mobile-first.

---

## 4. Component foundation

- **Base:** Tailwind CSS + **shadcn/ui** (Radix primitives — accessible, unstyled-then-themed).
- **Icons:** Lucide.
- **Charts:** Recharts.
- **3D:** react-three-fiber + drei.
- **Motion:** Framer Motion for page/element transitions.
- **Tables:** TanStack Table (sorting, filtering, virtualization for large order lists).

All components tokenized (CSS variables) so the whole brand can re-theme by changing tokens — no hardcoded colors.

---

## 5. Type & spacing (direction-independent)

- **Type scale:** display / h1 / h2 / body / caption — one sans for UI; an optional display face for headings gives the "brand" feel.
- **Spacing:** 8pt grid (4, 8, 12, 16, 24, 32, 48).
- **Radius:** consistent (e.g. 10–12px cards, pill buttons) — set per direction.
- **Elevation:** soft, layered shadows (not harsh borders) for the premium feel.

---

## 6. Decided

- **Name:** Cedarside Holding Corp. · **Direction:** Warm Craft.
- Full token set (colors, type, radius, shadows, dark/light) lives in [`DESIGN_SYSTEM.md`](./DESIGN_SYSTEM.md) and [`tokens.css`](./tokens.css), which the frontend consumes at scaffold time.
