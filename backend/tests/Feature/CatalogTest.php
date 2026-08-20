<?php

namespace Tests\Feature;

use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function customer(): User
    {
        $u = User::factory()->create();
        $u->assignRole('customer');

        return $u;
    }

    public function test_admin_creates_a_draft_product(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/products', ['name' => 'Oak Table', 'base_price' => 4999])
            ->assertCreated()
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.slug', 'oak-table');
    }

    public function test_options_endpoint_returns_dropdown_lists(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/products/options')
            ->assertOk()
            ->assertJsonStructure(['data' => ['categories', 'materials', 'wood_types', 'finishes']]);
    }

    public function test_creates_with_detailed_fields_and_validates_dropdowns(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/products', [
            'name' => 'Narra Dining Table',
            'category' => 'Tables',
            'material' => 'Solid Wood',
            'wood_type' => 'Narra',
            'finish' => 'Matte',
            'width_cm' => 180,
            'depth_cm' => 90,
            'height_cm' => 75,
            'weight_kg' => 45,
            'base_price' => 25000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.material', 'Solid Wood')
            ->assertJsonPath('data.dimensions_label', 'W180 × D90 × H75 cm');

        // Invalid category is rejected.
        $this->postJson('/api/v1/products', ['name' => 'X', 'category' => 'NotARealCategory'])
            ->assertStatus(422);
    }

    public function test_cannot_publish_without_a_3d_model(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::create(['name' => 'Chair', 'slug' => 'chair', 'base_price' => 1000]);

        $this->postJson("/api/v1/products/{$product->id}/publish")->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'DRAFT']);
    }

    public function test_publish_flow_and_customer_visibility(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);
        $product = Product::create(['name' => 'Bed', 'slug' => 'bed', 'base_price' => 8000]);

        $this->postJson("/api/v1/products/{$product->id}/model-versions", [
            'file' => UploadedFile::fake()->create('bed.glb', 100, 'model/gltf-binary'),
        ])->assertCreated();

        $this->postJson("/api/v1/products/{$product->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'PUBLISHED');

        // A draft product exists too.
        Product::create(['name' => 'Secret', 'slug' => 'secret', 'base_price' => 500]);

        // Customer sees only the published one.
        Sanctum::actingAs($this->customer());
        $res = $this->getJson('/api/v1/products')->assertOk()->json('data');
        $this->assertCount(1, $res);
        $this->assertSame('PUBLISHED', $res[0]['status']);
    }
}
