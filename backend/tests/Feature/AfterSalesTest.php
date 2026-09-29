<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AfterSalesTest extends TestCase
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

    private function order(User $customer, OrderState $status): Order
    {
        return Order::create([
            'order_number' => 'ORD-AS-'.uniqid(), 'customer_id' => $customer->id, 'status' => $status,
            'subtotal' => 1000, 'total' => 1620, 'payment_status' => 'PAID',
        ]);
    }

    public function test_customer_requests_a_return_on_a_delivered_order(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, OrderState::DELIVERED);
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->id}/returns", ['reason' => 'damaged', 'description' => 'Leg cracked'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'REQUESTED')
            ->assertJsonPath('data.reason', 'damaged');

        // A second active request is rejected.
        $this->postJson("/api/v1/orders/{$order->id}/returns", ['reason' => 'other'])->assertStatus(422);
    }

    public function test_return_cannot_be_requested_before_delivery(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, OrderState::IN_PRODUCTION);
        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->id}/returns", ['reason' => 'damaged'])->assertStatus(422);
    }

    public function test_admin_refunds_a_return_and_defaults_the_amount_to_the_order_total(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, OrderState::DELIVERED);
        Sanctum::actingAs($customer);
        $id = $this->postJson("/api/v1/orders/{$order->id}/returns", ['reason' => 'defective'])->json('data.id');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/returns/{$id}", ['status' => 'REFUNDED', 'resolution_note' => 'Refunded in full'])
            ->assertOk()
            ->assertJsonPath('data.status', 'REFUNDED')
            ->assertJsonPath('data.refund_amount', '1620.00');

        $this->getJson('/api/v1/returns')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_customer_reports_an_issue_and_admin_resolves_it(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, OrderState::DELIVERED);
        Sanctum::actingAs($customer);

        $id = $this->postJson("/api/v1/orders/{$order->id}/issues", ['category' => 'damaged_item', 'description' => 'Arrived scratched'])
            ->assertCreated()->assertJsonPath('data.status', 'OPEN')->json('data.id');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/issues/{$id}", ['status' => 'RESOLVED', 'resolution_note' => 'Replacement sent'])
            ->assertOk()->assertJsonPath('data.status', 'RESOLVED');
    }

    public function test_a_customer_cannot_open_a_return_on_someone_elses_order(): void
    {
        $owner = $this->customer();
        $order = $this->order($owner, OrderState::DELIVERED);

        Sanctum::actingAs($this->customer());
        $this->postJson("/api/v1/orders/{$order->id}/returns", ['reason' => 'damaged'])->assertForbidden();
    }
}
