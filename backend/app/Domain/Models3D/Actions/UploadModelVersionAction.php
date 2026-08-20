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
        return DB::transaction(function () use ($product, $file, $uploader, $changeLog) {
            $model = $product->model()->firstOrCreate([]);

            $next = (int) $model->versions()->max('version') + 1;
            $path = $file->store("models/products/{$product->id}");

            $version = $model->versions()->create([
                'version' => $next,
                'file_path' => $path,
                'format' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'change_log' => $changeLog,
                'uploaded_by' => $uploader->id,
            ]);

            $model->update(['current_version_id' => $version->id]);

            return $version;
        });
    }
}
