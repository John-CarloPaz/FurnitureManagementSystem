<?php

namespace App\Http\Resources;

use App\Domain\Manufacturing\Models\QualityInspection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin QualityInspection */
class QualityInspectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'passed' => $this->passed,
            'reason' => $this->reason,
            'attempt' => $this->attempt,
            'inspector' => $this->whenLoaded('inspector', fn () => $this->inspector?->name),
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => URL::temporarySignedRoute(
                    'quality-photos.file',
                    now()->addHour(),
                    ['photo' => $photo->id],
                    absolute: false,
                ),
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
