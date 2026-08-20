# Products / Catalog

The company's product catalog. Admin prepares products (each with a 3D model) and **publishes** them; only published products are visible to customers.

## Purpose
Give the furniture company a managed catalog of products customers can browse and order. Owns the **3D model** (moved here from Order in the 2026-07-07 realignment).

## Responsibilities
- Product CRUD + the **DRAFT → PUBLISHED → ARCHIVED** lifecycle ([FSM.md §1](../design/FSM.md)).
- The 3D model per product (upload, versioning, signed URLs) — see [3d-viewer.md](./3d-viewer.md).
- Visibility: staff see all; customers see only PUBLISHED.

## Product fields & dropdowns
Detailed furniture attributes: `category`, `material`, `wood_type`, `finish` (validated **dropdowns**), structured `width_cm` / `depth_cm` / `height_cm` / `weight_kg`, plus `base_price`, `lead_time_days`, `description`. The resource exposes a computed `dimensions_label` (e.g. `W180 × D90 × H75 cm`).

Dropdown option lists live in **`backend/config/catalog.php`** (single source of truth — edit there to change choices) and are served to the SPA via `GET /api/v1/products/options`. Create/update validate against these lists (`Rule::in`).

## Public API
| Method | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/products` | policy `viewAny` | staff → all (filter `?status`); customers → published only |
| POST | `/api/v1/products` | `products.manage` | create (DRAFT, auto-slug) |
| GET | `/api/v1/products/{product}` | policy `view` | detail (published-only for customers) |
| PATCH | `/api/v1/products/{product}` | `products.manage` | edit |
| DELETE | `/api/v1/products/{product}` | `products.manage` | delete |
| POST | `/api/v1/products/{product}/publish` | `products.publish` | → PUBLISHED (guarded) |
| POST | `/api/v1/products/{product}/unpublish` | `products.publish` | → DRAFT |
| POST | `/api/v1/products/{product}/archive` | `products.publish` | → ARCHIVED |
| GET/POST | `/api/v1/products/{product}/model-versions` | policy | list / upload 3D |

## Domain classes
| Class | Responsibility |
|---|---|
| `Products\Models\Product` (+ `ProductImage`) | catalog entity; `status` cast to `ProductStatus` |
| `Products\Enums\ProductStatus` | DRAFT/PUBLISHED/ARCHIVED + allowed transitions |
| `Products\Actions\ChangeProductStatusAction` | status change + **publish guard** (needs a 3D version + `base_price > 0`) |
| `Products\Policies\ProductPolicy` | viewAny/view/create/update/delete/publish |
| `Products\ProductsServiceProvider` | registers the policy |

## Publish guard
`ChangeProductStatusAction` blocks `→ PUBLISHED` unless the product has a 3D model version **and** a price above 0 — a product can't reach customers half-built.

## Tests
`CatalogTest`: admin creates draft; publish blocked without 3D (422); upload → publish → customer sees only published. Larastan L6 clean.
