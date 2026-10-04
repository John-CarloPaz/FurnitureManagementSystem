<?php

namespace App\Http\Controllers\Api;

use App\Domain\Access\PermissionCatalog;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** System ownership — only the current owner (the root super-admin) may transfer it. */
class OwnerController extends Controller
{
    public function transfer(Request $request): UserResource
    {
        $actor = $request->user();
        abort_unless($actor !== null && $actor->isOwner(), 403, 'Only the system owner can transfer ownership.');

        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $target = User::findOrFail($validated['user_id']);

        if ($target->id === $actor->id) {
            throw ValidationException::withMessages(['user_id' => ['You already own the system.']]);
        }
        if (! $target->is_active) {
            throw ValidationException::withMessages(['user_id' => ['Ownership can only be transferred to an active user.']]);
        }

        DB::transaction(function () use ($actor, $target) {
            $target->syncRoles([PermissionCatalog::SUPER_ADMIN]); // the new owner must be a super admin
            $actor->forceFill(['is_owner' => false])->save();
            $target->forceFill(['is_owner' => true])->save();
        });

        return new UserResource($target->refresh());
    }
}
