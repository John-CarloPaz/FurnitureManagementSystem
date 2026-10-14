<?php

namespace Tests\Feature;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Jobs\ProcessModelGeneration;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_uploads_a_model_for_a_variation_and_resync_preserves_it(): void
    {
        Storage::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::create(['name' => 'Oak', 'slug' => 'oak-up', 'base_price' => 4000, 'status' => ProductStatus::PUBLISHED]);
        $variation = $product->variations()->create(['name' => 'Walnut', 'color_hex' => '#5b3a29', 'sort_order' => 0]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/products/{$product->id}/variations/{$variation->id}/model", [
            'file' => UploadedFile::fake()->create('chair.glb', 120),
        ])->assertOk()->assertJsonPath('data.has_model', true);

        $this->assertNotNull($variation->fresh()->model_path);

        // Re-syncing colours (by id) keeps the uploaded model.
        $this->putJson("/api/v1/products/{$product->id}/variations", [
            'variations' => [['id' => $variation->id, 'name' => 'Dark Walnut', 'color_hex' => '#4a2f20']],
        ])->assertOk()->assertJsonPath('data.0.has_model', true);
        $this->assertNotNull($variation->fresh()->model_path);
    }

    public function test_generate_stores_the_photo_and_queues_a_variation_generation(): void
    {
        Storage::fake();
        Queue::fake();
        $this->mock(ModelGenerator::class, fn ($m) => $m->shouldReceive('isConfigured')->andReturnTrue());

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::create(['name' => 'Oak', 'slug' => 'oak-gen', 'base_price' => 4000, 'status' => ProductStatus::PUBLISHED]);
        $variation = $product->variations()->create(['name' => 'Natural', 'color_hex' => '#c9a063', 'sort_order' => 0]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/products/{$product->id}/variations/{$variation->id}/generate", [
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertOk()->assertJsonStructure(['data' => ['image_url']]);

        $this->assertNotNull($variation->fresh()->image_path);
        $this->assertDatabaseHas('model_generations', ['variation_id' => $variation->id, 'product_id' => $product->id]);
        Queue::assertPushed(ProcessModelGeneration::class);
    }
}
