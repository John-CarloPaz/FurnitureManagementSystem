<?php

namespace Tests\Feature;

use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Models\ProductReview;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_insights_report_per_product_metrics(): void
    {
        $customer = User::factory()->create();
        $product = Product::create(['name' => 'Oak', 'slug' => 'oak-ins', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);

        // Delivered on time.
        $delivered = Order::create([
            'order_number' => 'ORD-INS-1', 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED,
            'subtotal' => 2000, 'total' => 2000, 'payment_status' => 'PAID',
            'delivered_at' => now(), 'expected_delivery_at' => now()->addDay(),
        ]);
        $item = $delivered->items()->create(['product_id' => $product->id, 'product_name' => 'Oak', 'unit_price' => 1000, 'quantity' => 2, 'line_total' => 2000]);

        // Cancelled order — excluded from sales/counts.
        $cancelled = Order::create(['order_number' => 'ORD-INS-2', 'customer_id' => $customer->id, 'status' => OrderState::CANCELLED, 'subtotal' => 5000, 'total' => 5000, 'payment_status' => 'UNPAID']);
        $cancelled->items()->create(['product_id' => $product->id, 'product_name' => 'Oak', 'unit_price' => 1000, 'quantity' => 5, 'line_total' => 5000]);

        ProductReview::create(['product_id' => $product->id, 'user_id' => $customer->id, 'rating' => 4]);
        ManufacturingStage::create(['order_item_id' => $item->id, 'stage' => 'qc', 'status' => 'done', 'qc_passed' => false]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson("/api/v1/products/{$product->id}/insights")->assertOk();

        $this->assertEquals(2, $res->json('data.units_sold'));     // cancelled excluded
        $this->assertEquals(2000, $res->json('data.revenue'));
        $this->assertEquals(1, $res->json('data.orders_count'));
        $this->assertEquals(100, $res->json('data.on_time_rate'));
        $this->assertEquals(4, $res->json('data.rating_avg'));
        $this->assertEquals(1, $res->json('data.rating_count'));
        $this->assertEquals(100, $res->json('data.defect_rate'));  // 1 of 1 QC failed
        $this->assertEquals(1, $res->json('data.orders_by_status.DELIVERED'));
    }

    public function test_insights_report_best_selling_variation(): void
    {
        $customer = User::factory()->create();
        $product = Product::create(['name' => 'Bench', 'slug' => 'bench-ins', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);

        // Walnut: 3 units across one order.
        $o1 = Order::create(['order_number' => 'ORD-VAR-1', 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED, 'subtotal' => 3000, 'total' => 3000, 'payment_status' => 'PAID']);
        $o1->items()->create(['product_id' => $product->id, 'variation_name' => 'Walnut', 'product_name' => 'Bench', 'unit_price' => 1000, 'quantity' => 3, 'line_total' => 3000]);

        // Oak: 1 unit.
        $o2 = Order::create(['order_number' => 'ORD-VAR-2', 'customer_id' => $customer->id, 'status' => OrderState::DELIVERED, 'subtotal' => 1000, 'total' => 1000, 'payment_status' => 'PAID']);
        $o2->items()->create(['product_id' => $product->id, 'variation_name' => 'Oak', 'product_name' => 'Bench', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000]);

        // Oak units inside a cancelled order must not count.
        $cancelled = Order::create(['order_number' => 'ORD-VAR-3', 'customer_id' => $customer->id, 'status' => OrderState::CANCELLED, 'subtotal' => 9000, 'total' => 9000, 'payment_status' => 'UNPAID']);
        $cancelled->items()->create(['product_id' => $product->id, 'variation_name' => 'Oak', 'product_name' => 'Bench', 'unit_price' => 1000, 'quantity' => 9, 'line_total' => 9000]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $res = $this->getJson("/api/v1/products/{$product->id}/insights")->assertOk();

        $this->assertEquals('Walnut', $res->json('data.top_variant.name'));
        $this->assertEquals(3, $res->json('data.top_variant.units'));
    }

    public function test_customers_cannot_view_insights(): void
    {
        $product = Product::create(['name' => 'Chair', 'slug' => 'chair-ins', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/products/{$product->id}/insights")->assertForbidden();
    }
}
