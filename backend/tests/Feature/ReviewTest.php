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

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(): User
    {
        $u = User::factory()->create();
        $u->assignRole('customer');

        return $u;
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Stool', 'slug' => 'stool', 'base_price' => 800, 'status' => ProductStatus::PUBLISHED]);
    }

    private function deliveredOrder(User $customer, Product $product): void
    {
        $order = Order::create([
            'order_number' => 'ORD-R-'.uniqid(), 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED,
            'subtotal' => 800, 'total' => 800, 'payment_status' => 'PAID',
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 800, 'quantity' => 1, 'line_total' => 800]);
    }

    public function test_a_customer_with_a_delivered_order_can_review(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->deliveredOrder($customer, $product);
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/shop/products/{$product->id}/reviews", ['rating' => 5, 'comment' => 'Beautiful craftsmanship!'])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.is_mine', true);

        // Public listing + product rating aggregate.
        $this->getJson("/api/v1/shop/products/{$product->id}/reviews")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/shop/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 5)
            ->assertJsonPath('data.rating_count', 1);
    }

    public function test_reviewing_twice_updates_the_same_review(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->deliveredOrder($customer, $product);
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/shop/products/{$product->id}/reviews", ['rating' => 3])->assertCreated();
        $this->postJson("/api/v1/shop/products/{$product->id}/reviews", ['rating' => 4])->assertCreated();

        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_a_customer_without_a_delivered_order_cannot_review(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/shop/products/{$product->id}/reviews", ['rating' => 5])->assertForbidden();
    }
}
