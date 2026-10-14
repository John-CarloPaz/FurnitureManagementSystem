<?php

namespace Tests\Feature;

use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function driver(): User
    {
        $u = User::factory()->create();
        $u->assignRole('delivery_personnel');

        return $u;
    }

    private function order(OrderState $status): Order
    {
        $customer = User::factory()->create();

        return Order::create([
            'order_number' => 'ORD-REC-'.uniqid(), 'customer_id' => $customer->id, 'status' => $status,
            'subtotal' => 1000, 'total' => 1000, 'payment_status' => 'PAID',
            'expected_delivery_at' => now()->addDays(2),
        ]);
    }

    public function test_it_recommends_a_date_and_the_least_loaded_driver(): void
    {
        $busy = $this->driver();
        $idle = $this->driver();
        // Give the "busy" driver an active (not delivered) assignment.
        DeliveryAssignment::create(['order_id' => $this->order(OrderState::OUT_FOR_DELIVERY)->id, 'driver_id' => $busy->id, 'status' => 'out_for_delivery']);

        $order = $this->order(OrderState::READY_FOR_DELIVERY);

        $coordinator = User::factory()->create();
        $coordinator->assignRole('logistics_coordinator');
        Sanctum::actingAs($coordinator);

        $this->getJson("/api/v1/orders/{$order->id}/delivery-recommendation")
            ->assertOk()
            ->assertJsonPath('data.suggested_driver.id', $idle->id)
            ->assertJsonPath('data.suggested_driver.active_deliveries', 0)
            ->assertJsonStructure(['data' => ['recommended_date', 'reason', 'suggested_driver']]);
    }

    public function test_customers_cannot_see_the_recommendation(): void
    {
        $order = $this->order(OrderState::READY_FOR_DELIVERY);
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->id}/delivery-recommendation")->assertForbidden();
    }
}
