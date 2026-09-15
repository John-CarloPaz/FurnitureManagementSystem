# Module — Image → 3D Model Generation

Admins create a listing by uploading a **photo**; the system generates the product's 3D model (GLB) from it via an external image-to-3D service. Every listing gets its own model. Manual `.glb` upload remains as a fallback.

> This extends the original "users only upload models" scope: the photo is the input, and the 3D asset is generated for it.

## Flow
1. **Create listing with a photo** → the photo is saved as the listing's primary image, a `model_generations` row is created (`pending`), and a queued job is dispatched.
2. **`ProcessModelGeneration` (queued)** submits the photo (as a base64 data URI) to the provider, stores the returned task id (`processing`), then **re-queues itself every `poll_seconds`** to poll.
3. On **SUCCEEDED** it downloads `model_urls.glb` (the provider URL expires) and records it as the product's **current `Model3DVersion`** — the on-site 3D viewer then renders it. On **FAILED/timeout** the row is marked `failed` with a reason.
4. The SPA polls the product every 4s while status is `pending`/`processing`, showing a progress bar, then swaps in the viewer.

## Design
- **Provider-agnostic (DIP):** [`ModelGenerator`](../../backend/app/Domain/ModelGeneration/Contracts/ModelGenerator.php) is the seam; [`MeshyModelGenerator`](../../backend/app/Domain/ModelGeneration/Providers/MeshyModelGenerator.php) is the default driver. Swapping to Tripo / fal.ai is a new driver + a config line in `ModelGenerationServiceProvider` — the job, controller and UI don't change.
- **Async by construction:** all provider work is off-request on the queue, so a slow generation never blocks the create call. A `timeout_minutes` guard fails runaway tasks.
- **Graceful when unconfigured:** with no `MESHY_API_KEY`, `isConfigured()` is false — the photo is still stored, no generation is queued, and the admin uploads a `.glb` manually. The catalog options endpoint exposes `model_generation_enabled` so the UI adapts.
- **Reuses the model store:** generated GLBs go through the same `UploadModelVersionAction::record()` path as manual uploads, so versioning, the viewer, and publish rules are unchanged.

## Key files
| Layer | Files |
|---|---|
| Generation | `Domain/ModelGeneration/` — `Contracts/ModelGenerator.php`, `Providers/MeshyModelGenerator.php`, `Enums/GenerationStatus.php`, `GenerationResult.php`, `Models/ModelGeneration.php`, `Actions/GenerateProductModelAction.php`, `Jobs/ProcessModelGeneration.php`, `ModelGenerationServiceProvider.php` |
| Products | `Domain/Products/Actions/StoreProductImageAction.php` · `Http/Controllers/Api/ProductController.php` (`store`, `generateModel`, `options`) · `Http/Requests/Products/GenerateModelRequest.php` |
| Config | `config/model_generation.php` |
| Frontend | `components/catalog/{product-form,product-model-panel}.tsx` · `lib/products-api.ts` · `hooks/use-products.ts` |

## API
| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/products` | Multipart with `image` → creates listing + queues generation (`products.create`) |
| POST | `/api/v1/products/{product}/generate-model` | Regenerate — new `image`, or omit to reuse the last photo (`products.update`) |
| GET | `/api/v1/products/options` | Includes `model_generation_enabled` |

The product payload includes `model_generation: { status, progress, error }` (or null).

## Environment
```
MODEL_GENERATION_PROVIDER=meshy
MESHY_API_KEY=msy-...            # empty → generation disabled, manual upload only
MESHY_AI_MODEL=latest
MESHY_TARGET_POLYCOUNT=30000
MODEL_GENERATION_TIMEOUT_MINUTES=15
MODEL_GENERATION_POLL_SECONDS=15
```
**Requires a queue worker** (`php artisan queue:work`; a worker process on Laravel Cloud) — generation runs on the queue.

## Tests
`backend/tests/Feature/ModelGenerationTest.php` — create-with-photo queues a generation, unconfigured provider stores the image but skips generation, the options flag, and the job's submit → poll → finalize (into a current model version) and failure paths (Meshy HTTP faked).
