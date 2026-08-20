<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWith(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_dashboard_computes_lead_time_and_on_time_rate(): void
    {
        $customer = $this->userWith('customer');
        Order::create([
            'order_number' => 'ORD-D1', 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED,
            'total' => 1000, 'placed_at' => now()->subDays(3), 'delivered_at' => now(),
        ]);

        Sanctum::actingAs($this->userWith('admin'));

        $response = $this->getJson('/api/v1/kpi')
            ->assertOk()
            ->assertJsonStructure(['data' => ['headline' => ['ote', 'avg_lead_time_days', 'on_time_rate', 'defect_rate'], 'orders_by_status', 'bottleneck', 'deliveries_by_status']]);

        $this->assertEqualsWithDelta(3.0, $response->json('data.headline.avg_lead_time_days'), 0.1);
        $this->assertEqualsWithDelta(100, $response->json('data.headline.on_time_rate'), 0.1);
    }

    public function test_kpi_endpoints_are_role_scoped(): void
    {
        // Production manager: shop-floor yes, full dashboard no.
        Sanctum::actingAs($this->userWith('production_manager'));
        $this->getJson('/api/v1/kpi/shop-floor')->assertOk();
        $this->getJson('/api/v1/kpi')->assertForbidden();

        // Logistics: delivery yes, shop-floor no.
        Sanctum::actingAs($this->userWith('logistics_coordinator'));
        $this->getJson('/api/v1/kpi/delivery')->assertOk();
        $this->getJson('/api/v1/kpi/shop-floor')->assertForbidden();
    }
}
