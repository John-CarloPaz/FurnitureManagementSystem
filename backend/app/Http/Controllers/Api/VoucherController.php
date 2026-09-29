<?php

namespace App\Http\Controllers\Api;

use App\Domain\Orders\Actions\ResolveVoucher;
use App\Domain\Orders\Models\Voucher;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\VoucherRequest;
use App\Http\Resources\VoucherResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VoucherController extends Controller
{
    /** Admin: list all vouchers. */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('vouchers.viewAny'), 403);

        return VoucherResource::collection(Voucher::query()->latest()->get());
    }

    public function store(VoucherRequest $request): JsonResponse
    {
        abort_unless($request->user()?->can('vouchers.create'), 403);

        $voucher = Voucher::create($request->validated());

        return (new VoucherResource($voucher))->response()->setStatusCode(201);
    }

    public function update(VoucherRequest $request, Voucher $voucher): VoucherResource
    {
        abort_unless($request->user()?->can('vouchers.update'), 403);

        $voucher->update($request->validated());

        return new VoucherResource($voucher->refresh());
    }

    public function destroy(Request $request, Voucher $voucher): JsonResponse
    {
        abort_unless($request->user()?->can('vouchers.delete'), 403);

        $voucher->delete();

        return response()->json(status: 204);
    }

    /** Any signed-in customer: preview whether a code applies to their cart subtotal. */
    public function preview(Request $request, ResolveVoucher $resolver): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $resolver->execute($validated['code'], (float) $validated['subtotal']);

        return response()->json([
            'data' => [
                'valid' => $result['error'] === null,
                'discount' => $result['discount'],
                'code' => $result['voucher']?->code,
                'message' => $result['error'],
            ],
        ]);
    }
}
