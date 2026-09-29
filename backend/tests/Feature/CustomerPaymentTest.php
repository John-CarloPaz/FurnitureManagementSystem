<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function order(User $customer, string $method): Order
    {
        return Order::create([
            'order_number' => 'ORD-PAY-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => OrderState::PLACED,
            'subtotal' => 1000, 'delivery_fee' => 500, 'tax_amount' => 120,
            'total' => 1620, 'payment_status' => 'UNPAID', 'payment_method' => $method,
        ]);
    }

    public function test_customer_pays_a_gcash_order(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = $this->order($customer, 'GCASH');
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->id}/pay", ['reference' => 'GC-123'])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'PAID')
            ->assertJsonPath('data.amount_paid', '1620.00');
    }

    public function test_cod_order_cannot_be_paid_online(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = $this->order($customer, 'COD');
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->id}/pay")->assertStatus(422);
    }

    public function test_a_stranger_cannot_pay_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $order = $this->order($owner, 'GCASH');

        $intruder = User::factory()->create();
        $intruder->assignRole('customer');
        Sanctum::actingAs($intruder);

        $this->postJson("/api/v1/orders/{$order->id}/pay")->assertForbidden();
    }
}
