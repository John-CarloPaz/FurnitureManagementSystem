<?php

namespace Tests\Feature;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QualityInspectionTest extends TestCase
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

    /** @return array{0: int, 1: int} [orderId, itemId] with production done and the order in QC. */
    private function orderInQualityCheck(): array
    {
        $product = Product::create(['name' => 'Chair', 'slug' => 'chair', 'base_price' => 5000, 'status' => ProductStatus::PUBLISHED]);

        Sanctum::actingAs($this->userWith('customer'));
        $orderId = (int) $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()->json('data.id');

        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'CONFIRMED'])->assertOk();

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'IN_PRODUCTION'])->assertOk();

        $itemId = (int) Order::with('items')->findOrFail($orderId)->items->first()->id;

        Sanctum::actingAs($this->userWith('manufacturing_operative'));
        foreach (['cutting', 'assembly', 'sanding', 'finishing'] as $stage) {
            $this->postJson("/api/v1/order-items/{$itemId}/stages/{$stage}/complete")->assertOk();
        }

        Sanctum::actingAs($this->userWith('production_manager'));
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'QUALITY_CHECK'])->assertOk();

        return [$orderId, $itemId];
    }

    public function test_qc_pass_closes_the_stage_and_lets_the_order_go_ready(): void
    {
        [$orderId, $itemId] = $this->orderInQualityCheck();

        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->post("/api/v1/order-items/{$itemId}/qc", ['passed' => '1'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.passed', true);

        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'READY_FOR_DELIVERY'])
            ->assertOk()->assertJsonPath('data.status', 'READY_FOR_DELIVERY');
    }

    public function test_operative_cannot_run_a_qc_inspection(): void
    {
        [, $itemId] = $this->orderInQualityCheck();

        Sanctum::actingAs($this->userWith('manufacturing_operative'));
        $this->post("/api/v1/order-items/{$itemId}/qc", ['passed' => '1'], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_qc_fail_requires_a_reason(): void
    {
        [, $itemId] = $this->orderInQualityCheck();

        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->post("/api/v1/order-items/{$itemId}/qc", ['passed' => '0'], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('reason');
    }

    public function test_qc_fail_records_reason_and_photos_resets_stages_and_reworks_the_order(): void
    {
        [$orderId, $itemId] = $this->orderInQualityCheck();

        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->post("/api/v1/order-items/{$itemId}/qc", [
            'passed' => '0',
            'reason' => 'Wobbly leg and a scratch on the top panel',
            'photos' => [UploadedFile::fake()->image('defect1.jpg'), UploadedFile::fake()->image('defect2.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.reason', 'Wobbly leg and a scratch on the top panel')
            ->assertJsonPath('data.attempt', 1)
            ->assertJsonCount(2, 'data.photos');

        // The order is reworked and the item repeats production (all stages reset → 0%).
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'REWORK']);

        $item = OrderItem::with('stages')->findOrFail($itemId);
        $this->assertTrue($item->stages->every(fn ($s) => $s->status->value === 'pending'));

        $this->assertDatabaseHas('quality_inspections', ['order_item_id' => $itemId, 'passed' => false, 'attempt' => 1]);
        $this->assertDatabaseCount('quality_inspection_photos', 2);
    }

    public function test_second_qc_attempt_increments_the_attempt_counter(): void
    {
        [, $itemId] = $this->orderInQualityCheck();
        $qa = $this->userWith('qa_tester');

        Sanctum::actingAs($qa);
        $this->post("/api/v1/order-items/{$itemId}/qc", ['passed' => '0', 'reason' => 'first defect'], ['Accept' => 'application/json'])->assertCreated();
        $this->post("/api/v1/order-items/{$itemId}/qc", ['passed' => '0', 'reason' => 'second defect'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.attempt', 2);
    }
}
