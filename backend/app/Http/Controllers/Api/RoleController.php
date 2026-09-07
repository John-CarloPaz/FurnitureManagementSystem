<?php

namespace App\Http\Controllers\Api;

use App\Domain\Access\PermissionCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Super-admin role builder. Reads/writes spatie roles; system roles (the seeded
 * eight) are locked so the app's default authorization can't be edited away.
 */
class RoleController extends Controller
{
    /** All roles with their granted permissions + how many users hold each. */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()->orderBy('id')->get();
        // Count per real instance: spatie's users() reads guard_name off the row, so
        // withCount()/loadCount() (which build from an empty instance) resolve to null.
        $roles->each(fn (Role $role) => $role->setAttribute('users_count', $role->users()->count()));

        return RoleResource::collection($roles);
    }

    /** The grouped permission catalog that drives the builder matrix. */
    public function permissions(): JsonResponse
    {
        return response()->json(['data' => PermissionCatalog::GROUPS]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->string('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions'));

        return (new RoleResource($role->setAttribute('users_count', $role->users()->count())))->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $this->assertNotSystemRole($role, 'System roles cannot be edited.');

        if ($request->filled('name')) {
            $role->update(['name' => $request->string('name')]);
        }
        $role->syncPermissions($request->validated('permissions'));

        return new RoleResource($role->setAttribute('users_count', $role->users()->count()));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->assertNotSystemRole($role, 'System roles cannot be deleted.');

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Reassign the users on this role before deleting it.'],
            ]);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    private function assertNotSystemRole(Role $role, string $message): void
    {
        if (PermissionCatalog::isSystemRole($role->name)) {
            throw ValidationException::withMessages(['role' => [$message]]);
        }
    }
}
