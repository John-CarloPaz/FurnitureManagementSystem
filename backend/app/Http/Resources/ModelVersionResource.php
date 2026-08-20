<?php

namespace App\Http\Resources;

use App\Domain\Models3D\Models\Model3DVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Model3DVersion */
class ModelVersionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'format' => $this->format,
            'file_size' => $this->file_size,
            'change_log' => $this->change_log,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
        ];
    }
}
