<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'country' => 'Philippines',
            'province_code' => '0722',
            'province_name' => 'Cebu',
            'city_code' => '072217',
            'city_name' => 'Cebu City',
            'barangay_code' => '072217001',
            'barangay_name' => 'Apas',
            'street' => '123 Mango Ave.',
            'landmark' => 'Near the church',
            'notes' => 'Leave at the gate',
            'is_default' => true,
        ], $overrides);
    }

    public function test_customer_saves_and_lists_addresses_default_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/addresses', $this->payload(['label' => 'Office', 'is_default' => false]))->assertCreated();
        $this->postJson('/api/v1/addresses', $this->payload(['label' => 'Home', 'is_default' => true]))
            ->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default', true);

        $list = $this->getJson('/api/v1/addresses')->assertOk()->json('data');
        $this->assertCount(2, $list);
        $this->assertSame('Home', $list[0]['label']); // default first
        $this->assertStringContainsString('Brgy. Apas', $list[0]['formatted']);
    }

    public function test_setting_a_new_default_clears_the_previous_one(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = $user->addresses()->create($this->payload(['label' => 'A', 'is_default' => true]));
        $second = $user->addresses()->create($this->payload(['label' => 'B', 'is_default' => false]));

        $this->patchJson("/api/v1/addresses/{$second->id}", $this->payload(['is_default' => true]))->assertOk();

        $this->assertFalse($first->refresh()->is_default);
        $this->assertTrue($second->refresh()->is_default);
    }

    public function test_a_customer_cannot_touch_another_users_address(): void
    {
        $owner = User::factory()->create();
        $address = $owner->addresses()->create($this->payload());

        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/v1/addresses/{$address->id}", $this->payload())->assertForbidden();
        $this->deleteJson("/api/v1/addresses/{$address->id}")->assertForbidden();
    }

    public function test_customer_deletes_their_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create($this->payload());
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/addresses/{$address->id}")->assertNoContent();
        $this->assertDatabaseMissing('delivery_addresses', ['id' => $address->id]);
    }
}
