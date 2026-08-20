<?php

namespace App\Domain\Models3D\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $model_id
 * @property int $version
 * @property string $file_path
 * @property string $format
 * @property int|null $file_size
 * @property string|null $change_log
 * @property int $uploaded_by
 * @property Carbon|null $created_at
 */
class Model3DVersion extends Model
{
    protected $table = 'model_3d_versions';

    protected $fillable = [
        'model_id', 'version', 'file_path', 'format', 'file_size', 'change_log', 'uploaded_by',
    ];

    /** @return BelongsTo<Model3D, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(Model3D::class, 'model_id');
    }
}
