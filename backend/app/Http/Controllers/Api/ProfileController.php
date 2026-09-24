<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /** The signed-in user edits their own name, username, and (optionally) password. */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        if ($request->filled('password')) {
            if (! Hash::check((string) $request->string('current_password'), (string) $user->password)) {
                throw ValidationException::withMessages(['current_password' => ['Your current password is incorrect.']]);
            }
            $user->password = (string) $request->string('password'); // hashed by the model cast
        }

        $user->fill($request->safe()->only('name', 'username'));
        $user->save();

        return new UserResource($user->refresh());
    }
}
