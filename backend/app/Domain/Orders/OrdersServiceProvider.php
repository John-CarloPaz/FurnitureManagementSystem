<?php

namespace App\Domain\Orders;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Policies\OrderPolicy;
use App\Domain\Orders\States\GuardRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Shared guard registry; module guards are registered in boot().
        $this->app->singleton(GuardRegistry::class, fn () => new GuardRegistry);
    }

    public function boot(): void
    {
        // Order model lives outside App\Models, so register the policy explicitly.
        Gate::policy(Order::class, OrderPolicy::class);

        // FSM data guards are registered here by their owning modules.
        // e.g. Models3D registers DRAFT -> PENDING_APPROVAL (requires a 3D version).
    }
}
