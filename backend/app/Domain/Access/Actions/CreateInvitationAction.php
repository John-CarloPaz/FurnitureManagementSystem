<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;

/** Result of issuing an invitation: the record plus whether the email actually went out. */
class InvitationResult
{
    public function __construct(
        public readonly Invitation $invitation,
        public readonly bool $emailSent,
    ) {}
}

/** Creates (or refreshes) an invitation for an email + role and emails the link. */
class CreateInvitationAction
{
    public function __construct(private readonly SendInvitationEmail $email) {}

    public function execute(string $email, string $role, ?User $inviter): InvitationResult
    {
        // One live invitation per email: reissuing overwrites token + expiry.
        $invitation = Invitation::updateOrCreate(
            ['email' => $email],
            [
                'role' => $role,
                'token' => Str::random(64),
                'invited_by' => $inviter?->id,
                'expires_at' => now()->addDays((int) config('invitations.expires_days', 7)),
                'accepted_at' => null,
            ],
        );

        return new InvitationResult($invitation, $this->email->execute($invitation));
    }
}
