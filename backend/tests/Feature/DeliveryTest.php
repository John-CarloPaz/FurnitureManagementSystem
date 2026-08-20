<?php

namespace Tests\Feature;

use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function userWith(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function order(OrderState $status): Order
    {
        $customer = $this->userWith('customer');

        return Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => $status,
            'total' => 1000,
        ]);
    }

    private function assignmentFor(Order $order, User $driver): DeliveryAssignment
    {
        return DeliveryAssignment::create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'coordinator_id' => $this->userWith('logistics_coordinator')->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    public function test_logistics_assigns_a_driver(): void
    {
        $order = $this->order(OrderState::READY_FOR_DELIVERY);
        $driver = $this->userWith('delivery_personnel');
        Sanctum::actingAs($this->userWith('logistics_coordinator'));

        $this->postJson("/api/v1/orders/{$order->id}/delivery", ['driver_id' => $driver->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.driver_id', $driver->id);
    }

    public function test_cannot_assign_an_order_that_is_not_ready(): void
    {
        $order = $this->order(OrderState::IN_PRODUCTION);
        Sanctum::actingAs($this->userWith('logistics_coordinator'));

        $this->postJson("/api/v1/orders/{$order->id}/delivery", [])->assertStatus(422);
    }

    public function test_driver_dispatch_moves_order_out_for_delivery(): void
    {
        $order = $this->order(OrderState::READY_FOR_DELIVERY);
        $driver = $this->userWith('delivery_personnel');
        $assignment = $this->assignmentFor($order, $driver);
        Sanctum::actingAs($driver);

        $this->postJson("/api/v1/deliveries/{$assignment->id}/dispatch", ['manual_location' => 'Warehouse'])
            ->assertOk()
            ->assertJsonPath('data.status', 'out_for_delivery');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'OUT_FOR_DELIVERY']);
    }

    public function test_a_different_driver_cannot_dispatch(): void
    {
        $order = $this->order(OrderState::READY_FOR_DELIVERY);
        $assignment = $this->assignmentFor($order, $this->userWith('delivery_personnel'));
        Sanctum::actingAs($this->userWith('delivery_personnel')); // someone else

        $this->postJson("/api/v1/deliveries/{$assignment->id}/dispatch", [])->assertForbidden();
    }

    public function test_proof_of_delivery_marks_the_order_delivered(): void
    {
        $order = $this->order(OrderState::OUT_FOR_DELIVERY);
        $driver = $this->userWith('delivery_personnel');
        $assignment = $this->assignmentFor($order, $driver);
        Sanctum::actingAs($driver);

        $this->postJson("/api/v1/deliveries/{$assignment->id}/proof", [
            'photo' => UploadedFile::fake()->image('pod.jpg'),
            'recipient_name' => 'Juan Dela Cruz',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'DELIVERED']);
        $this->assertDatabaseHas('proof_of_deliveries', ['delivery_assignment_id' => $assignment->id]);
    }

    public function test_cannot_mark_delivered_without_proof(): void
    {
        $order = $this->order(OrderState::OUT_FOR_DELIVERY);
        $driver = $this->userWith('delivery_personnel');
        $this->assignmentFor($order, $driver);
        Sanctum::actingAs($driver);

        // Bare transition (no proof captured) is blocked by the delivery guard.
        $this->postJson("/api/v1/orders/{$order->id}/transition", ['to' => 'DELIVERED'])->assertStatus(422);
    }
}
