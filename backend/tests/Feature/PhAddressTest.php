<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhAddressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_provinces_are_returned_with_ncr_injected(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/provinces/' => Http::response([
                ['code' => '0722', 'name' => 'Cebu'],
                ['code' => '0102', 'name' => 'Ilocos Norte'],
            ]),
        ]);

        $names = collect($this->getJson('/api/v1/ph/provinces')->assertOk()->json('data'))
            ->pluck('name')->all();

        $this->assertContains('Cebu', $names);
        $this->assertContains('Metro Manila (NCR)', $names);
    }

    public function test_ncr_cities_come_from_the_region_endpoint(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/regions/130000000/cities-municipalities/' => Http::response([
                ['code' => '133900000', 'name' => 'City of Manila'],
            ]),
        ]);

        $this->getJson('/api/v1/ph/provinces/130000000/cities')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'City of Manila');
    }

    public function test_barangays_map_to_code_and_name(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/cities-municipalities/072217/barangays/' => Http::response([
                ['code' => '072217001', 'name' => 'Apas'],
                ['code' => '072217002', 'name' => 'Banilad'],
            ]),
        ]);

        $data = $this->getJson('/api/v1/ph/cities/072217/barangays')->assertOk()->json('data');

        $this->assertSame(['code' => '072217001', 'name' => 'Apas'], $data[0]);
    }
}
