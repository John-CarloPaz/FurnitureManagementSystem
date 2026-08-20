<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
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

    private function published(float $price = 1000): Product
    {
        return Product::create([
            'name' => 'P'.uniqid(),
            'slug' => 'p-'.uniqid(),
            'base_price' => $price,
            'status' => ProductStatus::PUBLISHED,
        ]);
    }

    private function orderFor(User $customer, OrderState $status, float $total = 1000): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => $status,
            'total' => $total,
        ]);
    }

    public function test_customer_places_a_multi_item_order(): void
    {
        $customer = $this->userWith('customer');
        Sanctum::actingAs($customer);
        $a = $this->published(1000);
        $b = $this->published(500);

        $this->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 2],
                ['product_id' => $b->id, 'quantity' => 1],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'PLACED')
            ->assertJsonPath('data.total', '2500.00');

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_early_transitions_are_owned_by_the_right_roles(): void
    {
        $customer = $this->userWith('customer');
        $order = $this->orderFor($customer, OrderState::PLACED);
        $uri = "/api/v1/orders/{$order->id}/transition";

        // Production manager may NOT confirm (that's Admin's step).
        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson($uri, ['to' => 'CONFIRMED'])->assertForbidden();

        // Admin confirms.
        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson($uri, ['to' => 'CONFIRMED'])->assertOk()->assertJsonPath('data.status', 'CONFIRMED');

        // Customer may NOT start production; Production Manager can.
        Sanctum::actingAs($customer);
        $this->postJson($uri, ['to' => 'IN_PRODUCTION'])->assertForbidden();
        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson($uri, ['to' => 'IN_PRODUCTION'])->assertOk()->assertJsonPath('data.status', 'IN_PRODUCTION');
    }

    public function test_delivery_step_ownership_and_completion(): void
    {
        // Dispatch is owned by logistics/delivery — QA cannot (ownership check, before the guard).
        $ready = $this->orderFor($this->userWith('customer'), OrderState::READY_FOR_DELIVERY);
        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->postJson("/api/v1/orders/{$ready->id}/transition", ['to' => 'OUT_FOR_DELIVERY'])->assertForbidden();

        // Admin completes a delivered order (the full dispatch→proof flow is in DeliveryTest).
        $delivered = $this->orderFor($this->userWith('customer'), OrderState::DELIVERED);
        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson("/api/v1/orders/{$delivered->id}/transition", ['to' => 'COMPLETED'])
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED');
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $order = $this->orderFor($this->userWith('customer'), OrderState::PLACED);
        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson("/api/v1/orders/{$order->id}/transition", ['to' => 'DELIVERED'])->assertStatus(422);
    }

    public function test_customer_can_cancel_own_placed_order_but_not_confirm_it(): void
    {
        $customer = $this->userWith('customer');
        $order = $this->orderFor($customer, OrderState::PLACED);
        Sanctum::actingAs($customer);

        // Confirming is Admin's step, not the customer's.
        $this->postJson("/api/v1/orders/{$order->id}/transition", ['to' => 'CONFIRMED'])->assertForbidden();
        // Can cancel their own placed order.
        $this->postJson("/api/v1/orders/{$order->id}/transition", ['to' => 'CANCELLED'])
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED');
    }

    public function test_admin_records_payments_updating_status(): void
    {
        $order = $this->orderFor($this->userWith('customer'), OrderState::CONFIRMED, 2500);
        Sanctum::actingAs($this->userWith('admin'));
        $uri = "/api/v1/orders/{$order->id}/payments";

        $this->postJson($uri, ['amount' => 1000])->assertOk()->assertJsonPath('data.payment_status', 'PARTIAL');
        $this->postJson($uri, ['amount' => 1500])->assertOk()->assertJsonPath('data.payment_status', 'PAID');
    }
}
