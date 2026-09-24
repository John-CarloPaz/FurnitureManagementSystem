<?php

namespace App\Domain\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One immutable record of a data change: who, what entity, which fields, via which
 * HTTP method. Written by AuditRecorder on every authenticated write.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string $event
 * @property string|null $method
 * @property string|null $path
 * @property string $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 */
class AuditLog extends Model
{
    /** Audit rows are append-only — only created_at is tracked, set explicitly. */
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'event', 'method', 'path',
        'auditable_type', 'auditable_id', 'changes', 'ip_address', 'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
