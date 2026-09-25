<?php

namespace App\Http\Controllers\Api;

use App\Domain\Orders\Models\DeliveryAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\DeliveryAddressRequest;
use App\Http\Resources\DeliveryAddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/** A signed-in customer's address book — saved addresses for repeat orders. */
class DeliveryAddressController extends Controller
{
    /** The current user's saved addresses, default first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return DeliveryAddressResource::collection($addresses);
    }

    public function store(DeliveryAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $address = DB::transaction(function () use ($user, $data): DeliveryAddress {
            $address = $user->addresses()->create($data);
            $this->syncDefault($address);

            return $address;
        });

        return (new DeliveryAddressResource($address))->response()->setStatusCode(201);
    }

    public function update(DeliveryAddressRequest $request, DeliveryAddress $address): DeliveryAddressResource
    {
        $this->authorizeOwner($request, $address);

        DB::transaction(function () use ($address, $request): void {
            $address->update($request->validated());
            $this->syncDefault($address);
        });

        return new DeliveryAddressResource($address->refresh());
    }

    public function destroy(Request $request, DeliveryAddress $address): JsonResponse
    {
        $this->authorizeOwner($request, $address);

        $address->delete();

        return response()->json(status: 204);
    }

    /** A default address is exclusive — clear the flag on the user's other addresses. */
    private function syncDefault(DeliveryAddress $address): void
    {
        if (! $address->is_default) {
            return;
        }

        DeliveryAddress::query()
            ->where('user_id', $address->user_id)
            ->whereKeyNot($address->getKey())
            ->update(['is_default' => false]);
    }

    private function authorizeOwner(Request $request, DeliveryAddress $address): void
    {
        abort_unless($address->user_id === $request->user()?->id, 403);
    }
}
