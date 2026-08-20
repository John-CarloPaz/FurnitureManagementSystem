<?php

namespace App\Http\Controllers\Api;

use App\Domain\Models3D\Actions\UploadModelVersionAction;
use App\Domain\Models3D\Models\Model3DVersion;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Models\UploadModelVersionRequest;
use App\Http\Resources\ModelVersionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModelVersionController extends Controller
{
    /** List a product's 3D versions (newest first). */
    public function index(Product $product): AnonymousResourceCollection
    {
        $this->authorize('view', $product);

        $versions = $product->model?->versions()->get() ?? collect();

        return ModelVersionResource::collection($versions);
    }

    /** Upload a new .glb/.obj version for a product (products.manage). */
    public function store(UploadModelVersionRequest $request, Product $product, UploadModelVersionAction $action): JsonResponse
    {
        $this->authorize('update', $product);

        $version = $action->execute(
            $product,
            $request->file('file'),
            $request->user(),
            $request->validated('change_log'),
        );

        return (new ModelVersionResource($version))->response()->setStatusCode(201);
    }

    /** 1-hour relative signed URL to stream the file (caps §4.4). */
    public function download(Model3DVersion $version): JsonResponse
    {
        $this->authorize('view', $version->model->product);

        $url = URL::temporarySignedRoute(
            'model-versions.file',
            now()->addHour(),
            ['version' => $version->id],
            absolute: false,
        );

        return response()->json(['data' => ['url' => $url]]);
    }

    /** Stream the file — protected by the signed URL only. */
    public function file(Model3DVersion $version): StreamedResponse
    {
        return Storage::disk('local')->download(
            $version->file_path,
            "model-v{$version->version}.{$version->format}",
        );
    }
}
