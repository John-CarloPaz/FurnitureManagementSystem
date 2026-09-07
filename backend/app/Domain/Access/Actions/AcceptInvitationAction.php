<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Turns a valid invitation into an active user with the invited role. */
class AcceptInvitationAction
{
    public function execute(Invitation $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $invitation->email,   // fixed by the invite — the invitee can't change it
                'password' => $password,         // hashed by the model's `hashed` cast
                'is_active' => true,
            ]);

            $user->assignRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }
}
