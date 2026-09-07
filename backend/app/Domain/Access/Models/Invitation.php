<?php

namespace App\Domain\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A pending invitation to join the company under a given role. The raw token is a
 * single-use, expiring capability emailed to the invitee; accepting it creates the user.
 *
 * @property int $id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property int|null $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon $created_at
 */
class Invitation extends Model
{
    protected $fillable = ['email', 'role', 'token', 'invited_by', 'expires_at', 'accepted_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** The SPA accept link the invitee opens (frontend origin, not the API). */
    public function acceptUrl(): string
    {
        return config('invitations.frontend_url').config('invitations.accept_path').'/'.$this->token;
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        if ($this->accepted_at !== null) {
            return 'accepted';
        }

        return $this->expires_at->isPast() ? 'expired' : 'pending';
    }

    /** @param  Builder<Invitation>  $query */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }
}
