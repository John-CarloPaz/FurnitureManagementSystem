<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxies the free PSGC (Philippine Standard Geographic Code) API so the storefront
 * address form can cascade Province → City/Municipality → Barangay. Proxying avoids
 * the upstream's missing CORS headers and lets us cache the (rarely-changing) data.
 * NCR has no provinces, so it's surfaced as a single "Metro Manila (NCR)" province.
 */
class PhAddressController extends Controller
{
    private const BASE = 'https://psgc.gitlab.io/api';

    private const NCR_REGION = '130000000';

    public function provinces(): JsonResponse
    {
        $data = Cache::remember('psgc:provinces', now()->addWeek(), function (): array {
            $provinces = $this->options('/provinces/');
            $provinces[] = ['code' => self::NCR_REGION, 'name' => 'Metro Manila (NCR)'];
            usort($provinces, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

            return $provinces;
        });

        return response()->json(['data' => $data]);
    }

    public function cities(string $province): JsonResponse
    {
        $data = Cache::remember("psgc:cities:{$province}", now()->addWeek(), function () use ($province): array {
            $path = $province === self::NCR_REGION
                ? '/regions/'.self::NCR_REGION.'/cities-municipalities/'
                : "/provinces/{$province}/cities-municipalities/";

            return $this->options($path);
        });

        return response()->json(['data' => $data]);
    }

    public function barangays(string $city): JsonResponse
    {
        $data = Cache::remember("psgc:barangays:{$city}", now()->addWeek(), function () use ($city): array {
            return $this->options("/cities-municipalities/{$city}/barangays/");
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Fetch a PSGC endpoint and reduce it to sorted {code, name} options.
     *
     * @return array<int, array{code: string, name: string}>
     */
    private function options(string $path): array
    {
        $response = Http::acceptJson()->timeout(10)->get(self::BASE.$path);

        if ($response->failed()) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $response->json() ?? [];

        return collect($rows)
            ->map(fn (array $row): array => [
                'code' => (string) ($row['code'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }
}
