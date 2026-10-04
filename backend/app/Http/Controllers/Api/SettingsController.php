<?php

namespace App\Http\Controllers\Api;

use App\Domain\Settings\Support\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Runtime shop settings (shipping fee, VAT rate) — editable by super admin (settings.manage). */
class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        return response()->json(['data' => $this->current()]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $validated = $request->validate([
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:1'], // a rate, e.g. 0.12 = 12%
        ]);

        Settings::set('shop.shipping_fee', $validated['shipping_fee']);
        Settings::set('shop.vat_rate', $validated['vat_rate']);

        return response()->json(['data' => $this->current()]);
    }

    /** @return array{shipping_fee: float, vat_rate: float} */
    private function current(): array
    {
        return [
            'shipping_fee' => (float) Settings::get('shop.shipping_fee', config('shop.shipping_fee')),
            'vat_rate' => (float) Settings::get('shop.vat_rate', config('shop.vat_rate')),
        ];
    }
}
