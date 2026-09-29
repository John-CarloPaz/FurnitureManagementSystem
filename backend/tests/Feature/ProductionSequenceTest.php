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

class ProductionSequenceTest extends TestCase
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

    private function itemInProduction(): int
    {
        $customer = $this->userWith('customer');
        $product = Product::create(['name' => 'Table', 'slug' => 'seq-table', 'base_price' => 5000, 'status' => ProductStatus::PUBLISHED]);

        Sanctum::actingAs($customer);
        $orderId = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()->json('data.id');

        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'CONFIRMED'])->assertOk();
        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'IN_PRODUCTION'])->assertOk();

        return Order::with('items')->find($orderId)->items->first()->id;
    }

    public function test_a_later_stage_is_blocked_until_the_prior_is_done(): void
    {
        $itemId = $this->itemInProduction();
        Sanctum::actingAs($this->userWith('manufacturing_operative'));

        // Assembly cannot start while Cutting is still pending.
        $this->postJson("/api/v1/order-items/{$itemId}/stages/assembly/start")
            ->assertStatus(422)->assertJsonValidationErrors('stage');

        // Nor can it be completed out of order.
        $this->postJson("/api/v1/order-items/{$itemId}/stages/assembly/complete")
            ->assertStatus(422);
    }

    public function test_stages_proceed_once_the_prior_is_done(): void
    {
        $itemId = $this->itemInProduction();
        Sanctum::actingAs($this->userWith('manufacturing_operative'));

        $this->postJson("/api/v1/order-items/{$itemId}/stages/cutting/complete")->assertOk();
        // Now assembly is unlocked.
        $this->postJson("/api/v1/order-items/{$itemId}/stages/assembly/start")
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
    }
}
