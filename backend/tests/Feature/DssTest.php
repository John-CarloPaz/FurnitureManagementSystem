<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DssTest extends TestCase
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

    private function confirmedOrderWith(Product $product): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(), 'customer_id' => $this->userWith('customer')->id,
            'status' => OrderState::CONFIRMED, 'total' => 1000, 'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => $product->base_price, 'quantity' => 1, 'line_total' => $product->base_price,
        ]);

        return $order;
    }

    public function test_schedule_sequences_by_earliest_due_date(): void
    {
        $urgent = Product::create(['name' => 'Urgent', 'slug' => 'u-'.uniqid(), 'base_price' => 500, 'lead_time_days' => 2, 'status' => ProductStatus::PUBLISHED]);
        $relaxed = Product::create(['name' => 'Relaxed', 'slug' => 'r-'.uniqid(), 'base_price' => 500, 'lead_time_days' => 10, 'status' => ProductStatus::PUBLISHED]);
        $this->confirmedOrderWith($relaxed);
        $this->confirmedOrderWith($urgent);

        Sanctum::actingAs($this->userWith('production_manager'));

        $plan = $this->postJson('/api/v1/dss/schedule')->assertOk()->json('data.plan');

        $this->assertSame('Urgent', $plan[0]['product_name']);
        $this->assertSame(1, $plan[0]['sequence']);
    }

    public function test_route_optimize_orders_stops_nearest_first(): void
    {
        Sanctum::actingAs($this->userWith('logistics_coordinator'));

        $res = $this->postJson('/api/v1/dss/route-optimize', [
            'start' => ['lat' => 0, 'lng' => 0],
            'stops' => [
                ['id' => 'A', 'lat' => 0, 'lng' => 3],
                ['id' => 'B', 'lat' => 0, 'lng' => 1],
                ['id' => 'C', 'lat' => 0, 'lng' => 2],
            ],
        ])->assertOk();

        $order = $res->json('data.order');
        $this->assertSame(['B', 'C', 'A'], array_column($order, 'id'));
        $this->assertGreaterThan(0, $res->json('data.total_km'));
    }

    public function test_bottlenecks_returns_a_recommendation(): void
    {
        Sanctum::actingAs($this->userWith('production_manager'));

        $this->getJson('/api/v1/dss/bottlenecks')
            ->assertOk()
            ->assertJsonStructure(['data' => ['bottleneck', 'recommendation']]);
    }

    public function test_customer_cannot_run_scheduling(): void
    {
        Sanctum::actingAs($this->userWith('customer'));
        $this->postJson('/api/v1/dss/schedule')->assertForbidden();
    }
}
