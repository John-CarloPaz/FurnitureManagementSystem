<?php

namespace Tests\Feature;

use App\Domain\Orders\Models\Voucher;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoucherPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function product(float $price): Product
    {
        return Product::create(['name' => 'Chair', 'slug' => 'chair-'.uniqid(), 'base_price' => $price, 'status' => ProductStatus::PUBLISHED]);
    }

    public function test_order_totals_include_flat_shipping_and_12_percent_vat(): void
    {
        Sanctum::actingAs($this->customer());

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $this->product(1000)->id, 'quantity' => 1]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '1000.00')
            ->assertJsonPath('data.delivery_fee', '500.00')
            ->assertJsonPath('data.tax_amount', '120.00')
            ->assertJsonPath('data.total', '1620.00')
            ->assertJsonPath('data.payment_method', 'COD');
    }

    public function test_percent_voucher_discounts_the_subtotal_before_tax(): void
    {
        Sanctum::actingAs($this->customer());
        Voucher::create(['code' => 'SAVE10', 'type' => Voucher::TYPE_PERCENT, 'value' => 10]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $this->product(1000)->id, 'quantity' => 1]],
            'voucher_code' => 'save10',
            'payment_method' => 'GCASH',
        ])
            ->assertCreated()
            ->assertJsonPath('data.discount_amount', '100.00')
            ->assertJsonPath('data.tax_amount', '108.00') // 12% of 900
            ->assertJsonPath('data.total', '1508.00')      // 900 + 500 shipping + 108 tax
            ->assertJsonPath('data.voucher_code', 'SAVE10')
            ->assertJsonPath('data.payment_method', 'GCASH');

        $this->assertSame(1, Voucher::firstWhere('code', 'SAVE10')->used_count);
    }

    public function test_expired_voucher_is_rejected(): void
    {
        Sanctum::actingAs($this->customer());
        Voucher::create(['code' => 'OLD', 'type' => Voucher::TYPE_FIXED, 'value' => 200, 'expires_at' => now()->subDay()]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $this->product(1000)->id, 'quantity' => 1]],
            'voucher_code' => 'OLD',
        ])->assertStatus(422)->assertJsonValidationErrors('voucher_code');
    }

    public function test_customer_can_preview_a_voucher(): void
    {
        Sanctum::actingAs($this->customer());
        Voucher::create(['code' => 'FIX200', 'type' => Voucher::TYPE_FIXED, 'value' => 200, 'min_spend' => 500]);

        $this->postJson('/api/v1/vouchers/preview', ['code' => 'FIX200', 'subtotal' => 1000])
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount', 200);

        $this->postJson('/api/v1/vouchers/preview', ['code' => 'FIX200', 'subtotal' => 100])
            ->assertOk()
            ->assertJsonPath('data.valid', false);
    }

    public function test_only_authorized_staff_manage_vouchers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/vouchers', ['code' => 'NEW5', 'type' => 'percent', 'value' => 5])->assertCreated();

        Sanctum::actingAs($this->customer());
        $this->getJson('/api/v1/vouchers')->assertForbidden();
        $this->postJson('/api/v1/vouchers', ['code' => 'HACK', 'type' => 'percent', 'value' => 5])->assertForbidden();
    }
}
