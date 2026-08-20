<?php

namespace Tests\Feature;

use App\Domain\Orders\Models\Order;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManufacturingTest extends TestCase
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

    private function placeOrderInProduction(): int
    {
        $customer = $this->userWith('customer');
        $product = Product::create(['name' => 'Table', 'slug' => 'table', 'base_price' => 5000, 'status' => ProductStatus::PUBLISHED]);

        Sanctum::actingAs($customer);
        $orderId = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()->json('data.id');

        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'CONFIRMED'])->assertOk();

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'IN_PRODUCTION'])->assertOk();

        return $orderId;
    }

    public function test_entering_production_seeds_five_stages_per_item(): void
    {
        $this->placeOrderInProduction();

        // 1 item × 5 stages.
        $this->assertDatabaseCount('manufacturing_stages', 5);
    }

    public function test_qc_cannot_start_before_all_items_finish(): void
    {
        $orderId = $this->placeOrderInProduction();

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'QUALITY_CHECK'])
            ->assertStatus(422);
    }

    public function test_full_production_and_qc_flow(): void
    {
        $orderId = $this->placeOrderInProduction();
        $itemId = Order::with('items')->find($orderId)->items->first()->id;

        // Operative completes the production stages.
        Sanctum::actingAs($this->userWith('manufacturing_operative'));
        foreach (['cutting', 'assembly', 'sanding', 'finishing'] as $stage) {
            $this->postJson("/api/v1/order-items/{$itemId}/stages/{$stage}/complete")
                ->assertOk()->assertJsonPath('data.status', 'done');
        }

        // PM submits to QC (guard now satisfied).
        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'QUALITY_CHECK'])
            ->assertOk()->assertJsonPath('data.status', 'QUALITY_CHECK');

        // Operative may NOT run the QC stage (that's QA's job).
        Sanctum::actingAs($this->userWith('manufacturing_operative'));
        $this->postJson("/api/v1/order-items/{$itemId}/stages/qc/complete", ['qc_passed' => true])
            ->assertForbidden();

        // QA passes QC, then moves the order to ready.
        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->postJson("/api/v1/order-items/{$itemId}/stages/qc/complete", ['qc_passed' => true])->assertOk();
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'READY_FOR_DELIVERY'])
            ->assertOk()->assertJsonPath('data.status', 'READY_FOR_DELIVERY');
    }

    public function test_shop_floor_lists_orders_in_production(): void
    {
        $this->placeOrderInProduction();

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->getJson('/api/v1/shop-floor')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'IN_PRODUCTION')
            ->assertJsonStructure(['data' => [['order_number', 'has_delay', 'items' => [['percent', 'stages']]]]]);
    }

    public function test_production_manager_assigns_a_work_order(): void
    {
        $orderId = $this->placeOrderInProduction();
        $itemId = Order::with('items')->find($orderId)->items->first()->id;
        $operative = $this->userWith('manufacturing_operative');

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson('/api/v1/work-orders', [
            'order_item_id' => $itemId,
            'assigned_to' => $operative->id,
            'sequence' => 1,
        ])->assertCreated()->assertJsonPath('data.assigned_to', $operative->id);

        // Operatives cannot create work orders (no assign permission).
        Sanctum::actingAs($operative);
        $this->postJson('/api/v1/work-orders', ['order_item_id' => $itemId])->assertForbidden();
        // …but they can see the one assigned to them.
        $this->getJson('/api/v1/work-orders')->assertOk()->assertJsonCount(1, 'data');
    }
}
