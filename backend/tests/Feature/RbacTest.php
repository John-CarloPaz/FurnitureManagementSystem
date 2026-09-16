<?php

namespace Tests\Feature;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users')->assertOk();
    }

    public function test_customer_cannot_list_users(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_super_admin_is_a_superset_of_admin(): void
    {
        // super_admin can list users AND perform admin-owned order transitions.
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::create(['name' => 'Desk', 'slug' => 'desk', 'base_price' => 1000, 'status' => ProductStatus::PUBLISHED]);
        Sanctum::actingAs($customer);
        $orderId = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated()->json('data.id');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/users')->assertOk();
        // PLACED → CONFIRMED is admin-owned; super_admin gets the admin bypass.
        $this->postJson("/api/v1/orders/{$orderId}/transition", ['to' => 'CONFIRMED'])
            ->assertOk()->assertJsonPath('data.status', 'CONFIRMED');
        // Payments are admin-only too.
        $this->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 500])->assertOk();
    }
}
