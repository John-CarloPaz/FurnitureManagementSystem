<?php

namespace Tests\Feature;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_only_super_admin_can_manage_settings(): void
    {
        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/v1/settings')->assertForbidden();
        $this->patchJson('/api/v1/settings', ['shipping_fee' => 300, 'vat_rate' => 0.1])->assertForbidden();

        Sanctum::actingAs($this->user('super_admin'));
        $this->getJson('/api/v1/settings')->assertOk()->assertJsonStructure(['data' => ['shipping_fee', 'vat_rate']]);
    }

    public function test_super_admin_updates_shipping_and_tax_and_it_affects_new_orders(): void
    {
        Sanctum::actingAs($this->user('super_admin'));

        $this->patchJson('/api/v1/settings', ['shipping_fee' => 250, 'vat_rate' => 0.05])
            ->assertOk()
            ->assertJsonPath('data.shipping_fee', 250)
            ->assertJsonPath('data.vat_rate', 0.05);

        // Public storefront settings now reflect the change.
        $this->getJson('/api/v1/shop/settings')
            ->assertOk()
            ->assertJsonPath('data.shipping_fee', 250)
            ->assertJsonPath('data.vat_rate', 0.05);

        // A new order is priced with the adjusted shipping + tax.
        $customer = $this->user('customer');
        $product = Product::create(['name' => 'Chair', 'slug' => 'set-chair', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()
            ->assertJsonPath('data.delivery_fee', '250.00')
            ->assertJsonPath('data.tax_amount', '50.00')   // 5% of 1000
            ->assertJsonPath('data.total', '1300.00');     // 1000 + 250 + 50
    }
}
