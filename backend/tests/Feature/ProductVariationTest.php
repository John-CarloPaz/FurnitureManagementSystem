<?php

namespace Tests\Feature;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_syncs_colour_variations_and_the_storefront_exposes_them(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::create(['name' => 'Oak Table', 'slug' => 'oak-table', 'base_price' => 4000, 'status' => ProductStatus::PUBLISHED]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/products/{$product->id}/variations", [
            'variations' => [
                ['name' => 'Walnut', 'color_hex' => '#5B3A29'],
                ['name' => 'Natural', 'color_hex' => '#C9A063'],
            ],
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Walnut')
            ->assertJsonPath('data.0.color_hex', '#5b3a29'); // normalised to lowercase

        // Re-syncing replaces the set.
        $this->putJson("/api/v1/products/{$product->id}/variations", [
            'variations' => [['name' => 'Ebony', 'color_hex' => '#2B2420']],
        ])->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseCount('product_variations', 1);

        // Public product detail includes the variations.
        $this->getJson("/api/v1/shop/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.variations.0.name', 'Ebony');
    }

    public function test_invalid_hex_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::create(['name' => 'Chair', 'slug' => 'chair-v', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/products/{$product->id}/variations", [
            'variations' => [['name' => 'Bad', 'color_hex' => 'blue']],
        ])->assertStatus(422);
    }
}
