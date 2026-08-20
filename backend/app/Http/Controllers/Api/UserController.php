<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /** Paginated user list (permission: users.view). */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()->latest()->paginate(20),
        );
    }

    /** Create a user and assign a role (permission: users.manage). */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create($request->safe()->except('role', 'password_confirmation'));
        $user->assignRole($request->string('role'));

        return new UserResource($user);
    }
}
