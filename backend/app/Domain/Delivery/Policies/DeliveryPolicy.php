<?php

namespace App\Domain\Delivery\Policies;

use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('delivery.view');
    }

    public function view(User $user, DeliveryAssignment $assignment): bool
    {
        if (! $user->can('delivery.view')) {
            return false;
        }

        // Coordinators/admin see all; drivers see only their own assignments.
        return $user->hasRole('admin')
            || $user->can('delivery.assign')
            || $assignment->driver_id === $user->id;
    }

    public function assign(User $user): bool
    {
        return $user->can('delivery.assign');
    }

    /** Dispatch + location logging — the assigned driver (or admin). */
    public function update(User $user, DeliveryAssignment $assignment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->can('delivery.update') && $assignment->driver_id === $user->id;
    }

    public function recordProof(User $user, DeliveryAssignment $assignment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->can('delivery.proof.upload') && $assignment->driver_id === $user->id;
    }
}
