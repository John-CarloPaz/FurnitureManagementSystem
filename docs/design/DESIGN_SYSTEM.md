# Design System — Cedarside Holding Corp. (Warm Craft)

The single source of truth for Cedarside Holding Corp.'s visual language. All values are tokenized in [`tokens.css`](./tokens.css) and consumed by Tailwind + shadcn/ui. **Never hardcode a color or size in a component** — reference a token.

---

## 1. Brand personality

Premium, warm, precise, human. Craftsmanship expressed through a modern CRM lens: cream canvases, walnut and amber accents, a characterful serif for headings, and a clean sans for everything functional.

---

## 2. Color palette

### Brand
| Token | Light | Dark | Use |
|---|---|---|---|
| `--walnut` (primary) | `#6F4A2F` | `#B98A5E` | Primary buttons, active nav, links |
| `--walnut-hover` | `#5A3B25` | `#C89A6E` | Hover/pressed |
| `--amber` (accent) | `#C8862B` | `#E0A94A` | Highlights, focus rings, small accents |
| `--amber-soft` | `#F3E2C4` | `#3A2E1C` | Accent backgrounds, badges |

### Neutrals (warm-tinted, never pure gray)
| Token | Light | Dark | Use |
|---|---|---|---|
| `--bg` | `#FAF6EF` | `#1C1815` | App background (cream / espresso) |
| `--surface` | `#FFFDF9` | `#262019` | Cards, panels |
| `--surface-2` | `#F1EADD` | `#2F2820` | Insets, table headers, hover rows |
| `--border` | `#E4D9C8` | `#3A3128` | Hairlines, dividers |
| `--text` | `#2B2320` | `#F3ECE0` | Primary text (warm ink) |
| `--text-muted` | `#6E6157` | `#A99C8C` | Secondary text, captions |

### Status colors (FSM states + semantics — consistent in both modes)
| State / meaning | Token | Hex |
|---|---|---|
| Draft / neutral | `--status-neutral` | `#8A8175` |
| Pending Approval / warning | `--status-warning` | `#C8862B` |
| Approved / success | `--status-success` | `#3E8E57` |
| In Production / active | `--status-active` | `#3B6FB0` |
| Quality Check / info | `--status-info` | `#7A5FA6` |
| Rework / alert | `--status-alert` | `#C1502E` |
| Ready / Out for Delivery | `--status-progress` | `#2F9E8F` |
| Delivered | `--status-done` | `#3E8E57` |
| Cancelled / danger | `--status-danger` | `#B23B3B` |

Each status also has a `-soft` background variant (12% tint) for pills.

---

## 3. Typography

| Role | Font | Notes |
|---|---|---|
| Display / headings | **Fraunces** (variable serif) | Soft, crafted character — the "brand" voice. h1–h2 only |
| UI / body | **Inter** (variable sans) | All functional text, labels, tables |
| Data / IDs / mono | **JetBrains Mono** | Order IDs, timestamps, code-like values |

Self-hosted (no CDN dependency). Scale (rem):

| Token | Size / line | Use |
|---|---|---|
| `--text-display` | 2.5 / 1.1 | Page hero (Fraunces) |
| `--text-h1` | 1.875 / 1.2 | Section titles (Fraunces) |
| `--text-h2` | 1.375 / 1.3 | Card titles (Fraunces or Inter 600) |
| `--text-base` | 1.0 / 1.5 | Body (Inter) |
| `--text-sm` | 0.875 / 1.4 | Secondary |
| `--text-xs` | 0.75 / 1.4 | Captions, table meta |

---

## 4. Spacing, radius, elevation

- **Spacing:** 8pt grid → `4, 8, 12, 16, 24, 32, 48, 64`.
- **Radius:** `--radius-sm 8px` (inputs), `--radius-md 12px` (cards), `--radius-lg 16px` (modals), `--radius-pill 9999px` (status pills, primary CTAs).
- **Elevation** (warm-tinted, soft — no harsh borders):
  - `--shadow-sm` `0 1px 2px rgba(43,35,32,.06)`
  - `--shadow-md` `0 4px 12px rgba(43,35,32,.08)`
  - `--shadow-lg` `0 12px 32px rgba(43,35,32,.12)`

---

## 5. Motion

- Durations: `--dur-fast 150ms`, `--dur-base 220ms`, `--dur-slow 320ms`.
- Easing: `--ease-out cubic-bezier(.16,1,.3,1)`.
- Patterns: skeleton loaders on fetch, optimistic UI, toast on mutation, **highlight-fade** on Pusher-driven row updates, count tick on KPI change. Respect `prefers-reduced-motion`.

---

## 6. Component conventions (shadcn/ui, re-themed)

- **Buttons:** primary = walnut fill / cream text, pill radius; secondary = surface + border; ghost for toolbars.
- **Status pill:** `-soft` bg + solid dot + label; used on every order/stage.
- **Cards:** `--surface`, `--radius-md`, `--shadow-sm`, 24px padding.
- **Tables:** TanStack Table; `--surface-2` header, hover row `--surface-2`, mono for IDs, right-aligned numerics.
- **FSM stepper:** horizontal on order detail; current step walnut, done steps amber-check, future muted.
- **Sidebar:** `--surface`, active item walnut-tinted with left accent bar.
- **3D viewer:** full-bleed dark canvas even in light mode; floating glass control dock (`backdrop-blur`, translucent surface).
- **Focus:** 2px `--amber` ring, always visible (a11y).

---

## 7. Theming & accessibility

- Light + dark via `.dark` class on `<html>`; all components read tokens, so theming is free.
- Target **WCAG AA** contrast (≥4.5:1 body text). The warm neutrals above are tuned for this.
- Keyboard-first: ⌘K palette, focus rings, Radix primitives give ARIA for free.
