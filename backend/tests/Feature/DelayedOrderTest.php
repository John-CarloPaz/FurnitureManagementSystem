<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DelayedOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_expected_delivery_is_set_from_the_product_lead_time(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::create(['name' => 'Table', 'slug' => 'lead-table', 'base_price' => 4000, 'lead_time_days' => 5, 'status' => ProductStatus::PUBLISHED]);
        Sanctum::actingAs($customer);

        $id = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()->json('data.id');

        $order = Order::find($id);
        $this->assertNotNull($order->expected_delivery_at);
        $this->assertEqualsWithDelta(5, now()->diffInDays($order->expected_delivery_at, false), 1);
    }

    public function test_command_flags_overdue_orders_notifies_and_issues_a_voucher(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-LATE-1', 'customer_id' => $customer->id, 'status' => OrderState::IN_PRODUCTION,
            'subtotal' => 1000, 'total' => 1000, 'payment_status' => 'PAID',
            'placed_at' => now()->subDays(20), 'expected_delivery_at' => now()->subDays(3), 'is_delayed' => false,
        ]);

        $this->artisan('orders:flag-delays')->assertSuccessful();

        $order->refresh();
        $this->assertTrue($order->is_delayed);
        $this->assertNotNull($order->delay_notified_at);
        $this->assertDatabaseCount('vouchers', 1); // goodwill compensation
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.brevo.com')
            && $r['to'][0]['email'] === $customer->email
            && str_contains((string) $r['subject'], 'quick update on order'));

        // Running again doesn't re-flag or re-notify.
        $this->artisan('orders:flag-delays')->assertSuccessful();
        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_delivered_orders_are_never_flagged(): void
    {
        $customer = User::factory()->create();
        Order::create([
            'order_number' => 'ORD-DONE-1', 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED,
            'subtotal' => 1000, 'total' => 1000, 'payment_status' => 'PAID',
            'expected_delivery_at' => now()->subDays(5), 'is_delayed' => false,
        ]);

        $this->artisan('orders:flag-delays')->assertSuccessful();
        $this->assertDatabaseCount('vouchers', 0);
    }
}
