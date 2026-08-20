<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\TransitionOwnership;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['orders.viewAny', 'orders.view.own']);
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->can('orders.viewAny')) {
            return true;
        }

        return $user->can('orders.view.own') && $order->customer_id === $user->id;
    }

    public function place(User $user): bool
    {
        return $user->can('orders.place');
    }

    public function recordPayment(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Role-owned transitions (docs/design/FSM.md §2). Admin may do any transition;
     * staff act on any order for transitions their role owns; customers only on
     * their own order for customer-owned transitions.
     */
    public function transition(User $user, Order $order, ?OrderState $to = null): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($to === null) {
            return false;
        }

        $roles = TransitionOwnership::rolesFor($order->status, $to);
        if (empty($roles)) {
            return false;
        }

        $staffRoles = array_values(array_diff($roles, ['customer']));
        if ($staffRoles && $user->hasAnyRole($staffRoles)) {
            return true;
        }

        return in_array('customer', $roles, true)
            && $user->hasRole('customer')
            && $order->customer_id === $user->id;
    }
}
