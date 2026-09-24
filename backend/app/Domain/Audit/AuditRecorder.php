<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes an audit row for a model change. Only records authenticated actions, so
 * seeders/queue jobs (no user) produce no noise — every real user write is logged
 * with the actor, the HTTP method, and the field-level diff.
 */
class AuditRecorder
{
    /** Fields never recorded (pure noise). */
    private const IGNORED = ['created_at', 'updated_at', 'remember_token'];

    /** Field-name needles whose VALUES are masked (records that they changed, not the value). */
    private const REDACT = ['password', 'token', 'secret', 'phone', 'address'];

    /**
     * Record a change that isn't a single Eloquent event — e.g. a role's permission
     * grants or a user's role assignment (both live in pivot tables the observer can't see).
     *
     * @param  array<string, mixed>|null  $changes
     */
    public function log(string $event, string $entityType, ?int $entityId, ?array $changes = null): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => $user->name,
            'event' => $event,
            'method' => request()->method(),
            'path' => request()->path(),
            'auditable_type' => $entityType,
            'auditable_id' => $entityId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function record(Model $model, string $event): void
    {
        $user = Auth::user();
        if ($user === null) {
            return; // only audit authenticated edits (skip seeders, queue jobs, public sign-up)
        }

        $changes = $this->changes($model, $event);
        if ($event === 'updated' && ($changes === null || $changes === [])) {
            return; // timestamp-only touch — nothing meaningful changed
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => $user->name,
            'event' => $event,
            'method' => request()->method(),
            'path' => request()->path(),
            'auditable_type' => class_basename($model),
            'auditable_id' => $model->getKey(),
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function changes(Model $model, string $event): ?array
    {
        if ($event === 'deleted') {
            return null;
        }

        if ($event === 'created') {
            $out = [];
            foreach ($model->getAttributes() as $field => $value) {
                if (in_array($field, self::IGNORED, true) || $field === $model->getKeyName()) {
                    continue;
                }
                $out[$field] = $this->clean($field, $value);
            }

            return $out ?: null;
        }

        // updated → {field: {old, new}}
        $out = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }
            $out[$field] = [
                'old' => $this->clean($field, $model->getOriginal($field)),
                'new' => $this->clean($field, $new),
            ];
        }

        return $out ?: null;
    }

    private function clean(string $field, mixed $value): mixed
    {
        $lower = strtolower($field);
        foreach (self::REDACT as $needle) {
            if (str_contains($lower, $needle)) {
                return '•••';
            }
        }

        return is_scalar($value) || $value === null ? $value : json_encode($value);
    }
}
