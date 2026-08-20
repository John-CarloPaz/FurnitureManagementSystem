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

class NotificationTest extends TestCase
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
            'name' => 'P'.uniqid(), 'slug' => 'p-'.uniqid(),
            'base_price' => $price, 'status' => ProductStatus::PUBLISHED,
        ]);
    }

    public function test_placing_an_order_notifies_the_customer_and_admins(): void
    {
        $admin = $this->userWith('admin');
        $customer = $this->userWith('customer');
        $product = $this->published();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $customer->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
    }

    public function test_customer_lists_and_marks_notifications_read(): void
    {
        $customer = $this->userWith('customer');
        $product = $this->published();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated();

        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('meta.unread_count', 1);

        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->getJson('/api/v1/notifications')->assertJsonPath('meta.unread_count', 0);
    }

    public function test_a_transition_notifies_the_customer(): void
    {
        $customer = $this->userWith('customer');
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(), 'customer_id' => $customer->id,
            'status' => OrderState::PLACED, 'total' => 1000,
        ]);
        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson("/api/v1/orders/{$order->id}/transition", ['to' => 'CONFIRMED'])->assertOk();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $customer->id]);
    }
}
