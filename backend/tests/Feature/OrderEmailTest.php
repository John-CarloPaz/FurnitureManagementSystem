<?php

namespace Tests\Feature;

use App\Domain\Notifications\Actions\SendOrderEmail;
use App\Domain\Notifications\Notifications\OrderStatusNotification;
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

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function publishedProduct(): Product
    {
        return Product::create(['name' => 'Oak Chair', 'slug' => 'oak-chair', 'base_price' => 3000, 'status' => ProductStatus::PUBLISHED]);
    }

    public function test_placing_an_order_emails_the_customer(): void
    {
        $customer = $this->customer();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $this->publishedProduct()->id, 'quantity' => 1]],
            'delivery_address' => '123 Mango Ave., Cebu City',
        ])->assertCreated();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.brevo.com')
            && $request['to'][0]['email'] === $customer->email
            && str_contains((string) $request['subject'], 'Order received'));
    }

    public function test_milestone_transition_emails_but_intermediate_does_not(): void
    {
        $customer = $this->customer();
        $order = Order::create([
            'order_number' => 'ORD-TEST-1',
            'customer_id' => $customer->id,
            'status' => OrderState::IN_PRODUCTION,
            'subtotal' => 3000,
            'total' => 3000,
        ]);

        // QUALITY_CHECK is not a customer milestone → no email.
        $order->customer?->notify(new OrderStatusNotification($order->id, $order->order_number, 'QUALITY_CHECK'));
        app(SendOrderEmail::class)->execute($order, 'QUALITY_CHECK');
        Http::assertNothingSent();

        // OUT_FOR_DELIVERY is a milestone → one email.
        app(SendOrderEmail::class)->execute($order, 'OUT_FOR_DELIVERY');
        Http::assertSent(fn ($request) => str_contains((string) $request['subject'], 'Out for delivery'));
    }
}
