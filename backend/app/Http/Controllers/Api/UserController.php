<?php

namespace App\Http\Controllers\Api;

use App\Domain\Audit\AuditRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /** Paginated user list (permission: users.view). */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()->latest()->paginate(20),
        );
    }

    /** Create a user and assign a role (permission: users.create). */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create($request->safe()->except('role', 'password_confirmation'));
        $user->assignRole($request->string('role'));

        return new UserResource($user);
    }

    /** Rename, activate/deactivate, or re-role a user (permission: users.update). */
    public function update(UpdateUserRequest $request, User $user, AuditRecorder $audit): UserResource
    {
        $this->assertNotSelf($request, $user, 'You cannot change your own account here.');

        $user->fill($request->safe()->only('name', 'is_active'))->save(); // name/is_active audited by the observer

        if ($request->filled('role')) {
            $oldRole = $user->getRoleNames()->first();
            $newRole = (string) $request->string('role');
            if ($oldRole !== $newRole) {
                $user->syncRoles([$newRole]);
                // Role assignment is a pivot change → log it explicitly.
                $audit->log('updated', 'User', $user->id, ['role' => ['old' => $oldRole, 'new' => $newRole]]);
            }
        }

        return new UserResource($user->refresh());
    }

    /** Remove a user (permission: users.delete). */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->assertNotSelf($request, $user, 'You cannot delete your own account.');

        $user->delete();

        return response()->json(null, 204);
    }

    private function assertNotSelf(Request $request, User $user, string $message): void
    {
        if ($request->user()?->id === $user->id) {
            throw ValidationException::withMessages(['user' => [$message]]);
        }
    }
}
