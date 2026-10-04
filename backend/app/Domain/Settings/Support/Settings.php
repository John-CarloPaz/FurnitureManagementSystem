<?php

namespace App\Domain\Settings\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Runtime-editable key/value settings (e.g. shop.shipping_fee, shop.vat_rate) that
 * override the config defaults. Cached so pricing reads don't hit the DB each time;
 * the cache is shared across containers via the cache store, and busted on every set.
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, string|int|float $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now()],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string> */
    private static function all(): array
    {
        /** @var array<string, string> $values */
        $values = Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('settings')->pluck('value', 'key')->all());

        return $values;
    }
}
