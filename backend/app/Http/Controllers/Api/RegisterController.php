<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /** Public customer sign-up → creates a customer account and logs them straight in. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'username' => $request->string('username'),
            'email' => $request->string('email'),
            'password' => $request->string('password'), // hashed by the model cast
            'is_active' => true,
        ]);

        $user->assignRole('customer');

        return response()->json([
            'data' => [
                'token' => $user->createToken('spa')->plainTextToken,
                'user' => new UserResource($user),
            ],
        ], 201);
    }
}
