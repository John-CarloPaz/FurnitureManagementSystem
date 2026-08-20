# 3D Model Viewer

Quotation module #1, caps Objective #6. **Upload-and-view only — the system never generates models.** The 3D model belongs to the **Product** (the company designs it once; customers view it).

## Purpose
Let the company attach a `.glb`/`.obj` to each product, keep a version history, and serve it securely; the SPA renders it (react-three-fiber). Customers inspect the model when browsing/ordering.

## Responsibilities
- Store client-provided `.glb`/`.obj` as immutable, incrementing **versions** per product.
- Serve them via short-lived **signed URLs** (IP protection, caps §4.4).
- Gate product publishing on the presence of a model (guard lives in [products-catalog](./products-catalog.md)).
- It does **not** render or generate 3D — rendering is the SPA's job.

## Public API
| Method | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/products/{product}/model-versions` | policy `view` | version history (newest first) |
| POST | `/api/v1/products/{product}/model-versions` | policy `update` (`products.manage`) | upload `.glb`/`.obj` (≤20MB) → new current |
| GET | `/api/v1/model-versions/{version}/download` | policy `view` | 1-hour **relative signed URL** |
| GET | `/api/v1/model-versions/{version}/file` | **signed** | streams the file (session-less) |

## Domain classes
| Class | Responsibility |
|---|---|
| `Models3D\Models\Model3D` / `Model3DVersion` | one model per **product** → many versions; `current_version_id` |
| `Models3D\Actions\UploadModelVersionAction` | store file, increment version, set current (transactional) |

## Security
- Auth-protected `/download` mints a 1-hour `temporarySignedRoute(absolute:false)`; the `/file` stream trusts only the signature (host-agnostic — works through the SPA dev proxy).
- Extension-validated (`glb`/`obj`), 20MB cap. Files under `storage/app/models/products/{id}/` (local dev → S3 in prod).

## Change from original M2
The 3D model + approval flow was originally on the Order; in the 2026-07-07 realignment it moved to the **Product**, and the customer "approve" step was removed (the product is published by the company, not approved per-order). Upload/versioning/signed-URL/viewer code is otherwise unchanged.

## Tests
Covered by `CatalogTest` (upload → publish). Larastan L6 clean.
