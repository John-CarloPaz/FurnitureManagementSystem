<?php

namespace App\Domain\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quality_inspection_id
 * @property string $path
 */
class QualityInspectionPhoto extends Model
{
    protected $fillable = ['quality_inspection_id', 'path'];

    /** @return BelongsTo<QualityInspection, $this> */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }
}
