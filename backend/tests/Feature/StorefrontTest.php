<?php

namespace Tests\Feature;

use App\Domain\Models3D\Models\Model3D;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_public_browses_only_published_products(): void
    {
        Product::create(['name' => 'Published Chair', 'slug' => 'pub-chair', 'base_price' => 3000, 'status' => ProductStatus::PUBLISHED]);
        Product::create(['name' => 'Secret Draft', 'slug' => 'draft', 'base_price' => 1000, 'status' => ProductStatus::DRAFT]);

        $res = $this->getJson('/api/v1/shop/products')->assertOk()->json('data');

        $this->assertCount(1, $res);
        $this->assertSame('Published Chair', $res[0]['name']);
    }

    public function test_public_product_detail_exposes_a_model_url(): void
    {
        $uploader = User::factory()->create();
        $product = Product::create(['name' => 'Oak Table', 'slug' => 'oak', 'base_price' => 9000, 'status' => ProductStatus::PUBLISHED]);
        $model = Model3D::create(['product_id' => $product->id]);
        $version = $model->versions()->create(['version' => 1, 'file_path' => 'models/x.glb', 'format' => 'glb', 'uploaded_by' => $uploader->id]);
        $model->update(['current_version_id' => $version->id]);

        $this->getJson("/api/v1/shop/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Oak Table')
            ->assertJsonPath('data.model_format', 'glb')
            ->assertJsonStructure(['data' => ['model_url']]);
    }

    public function test_draft_product_is_not_visible_on_the_storefront(): void
    {
        $draft = Product::create(['name' => 'Hidden', 'slug' => 'hidden', 'base_price' => 1, 'status' => ProductStatus::DRAFT]);

        $this->getJson("/api/v1/shop/products/{$draft->id}")->assertNotFound();
    }

    public function test_storefront_filters_by_category_and_lists_categories(): void
    {
        Product::create(['name' => 'Oak Chair', 'slug' => 'oak-chair', 'base_price' => 1000, 'category' => 'Chairs', 'status' => ProductStatus::PUBLISHED]);
        Product::create(['name' => 'Pine Table', 'slug' => 'pine-table', 'base_price' => 2000, 'category' => 'Tables', 'status' => ProductStatus::PUBLISHED]);
        Product::create(['name' => 'Draft Sofa', 'slug' => 'draft-sofa', 'base_price' => 5000, 'category' => 'Sofas', 'status' => ProductStatus::DRAFT]);

        $categories = $this->getJson('/api/v1/shop/categories')->assertOk()->json('data');
        $this->assertSame(['Chairs', 'Tables'], $categories);

        $filtered = $this->getJson('/api/v1/shop/products?category=Chairs')->assertOk()->json('data');
        $this->assertCount(1, $filtered);
        $this->assertSame('Oak Chair', $filtered[0]['name']);
    }

    public function test_storefront_sorts_by_price(): void
    {
        Product::create(['name' => 'Cheap', 'slug' => 'cheap', 'base_price' => 500, 'status' => ProductStatus::PUBLISHED]);
        Product::create(['name' => 'Pricey', 'slug' => 'pricey', 'base_price' => 9000, 'status' => ProductStatus::PUBLISHED]);

        $asc = $this->getJson('/api/v1/shop/products?sort=price_asc')->assertOk()->json('data');
        $this->assertSame('Cheap', $asc[0]['name']);

        $desc = $this->getJson('/api/v1/shop/products?sort=price_desc')->assertOk()->json('data');
        $this->assertSame('Pricey', $desc[0]['name']);
    }

    public function test_customer_can_register_and_is_logged_in(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Buyer',
            'username' => 'janebuyer',
            'email' => 'jane@example.com',
            'password' => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.username', 'janebuyer')
            ->assertJsonPath('data.user.roles.0', 'customer')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertTrue(User::where('email', 'jane@example.com')->first()->hasRole('customer'));
    }

    public function test_a_welcome_email_is_sent_on_registration(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Wel Come',
            'username' => 'welcome1',
            'email' => 'wel@example.com',
            'password' => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
        ])->assertCreated();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.brevo.com')
            && $request['to'][0]['email'] === 'wel@example.com'
            && str_contains((string) $request['subject'], 'Welcome'));
    }
}
