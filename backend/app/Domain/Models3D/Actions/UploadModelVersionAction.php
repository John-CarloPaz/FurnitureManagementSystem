<?php

namespace App\Domain\Models3D\Actions;

use App\Domain\Models3D\Models\Model3DVersion;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UploadModelVersionAction
{
    /** Store an uploaded .glb/.obj as the next version of a product's model and make it current. */
    public function execute(Product $product, UploadedFile $file, User $uploader, ?string $changeLog = null): Model3DVersion
    {
        $path = $file->store("models/products/{$product->id}");

        return $this->record(
            $product,
            $path,
            strtolower($file->getClientOriginalExtension()),
            $file->getSize(),
            $uploader->id,
            $changeLog,
        );
    }

    /**
     * Record an already-stored model file as the next (current) version. Shared by the
     * manual upload flow and AI generation (which downloads the GLB, then records it).
     */
    public function record(Product $product, string $storedPath, string $format, ?int $fileSize, ?int $uploaderId, ?string $changeLog = null): Model3DVersion
    {
        return DB::transaction(function () use ($product, $storedPath, $format, $fileSize, $uploaderId, $changeLog) {
            $model = $product->model()->firstOrCreate([]);

            $version = $model->versions()->create([
                'version' => (int) $model->versions()->max('version') + 1,
                'file_path' => $storedPath,
                'format' => $format,
                'file_size' => $fileSize,
                'change_log' => $changeLog,
                'uploaded_by' => $uploaderId,
            ]);

            $model->update(['current_version_id' => $version->id]);

            return $version;
        });
    }
}
