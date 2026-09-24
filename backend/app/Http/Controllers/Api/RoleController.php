<?php

namespace App\Http\Controllers\Api;

use App\Domain\Access\PermissionCatalog;
use App\Domain\Audit\AuditRecorder;
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

    public function store(StoreRoleRequest $request, AuditRecorder $audit): JsonResponse
    {
        $permissions = $request->validated('permissions');
        $role = Role::create(['name' => $request->string('name'), 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $audit->log('created', 'Role', $role->id, ['name' => $role->name, 'permissions' => $permissions]);

        return (new RoleResource($role->setAttribute('users_count', $role->users()->count())))->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role, AuditRecorder $audit): RoleResource
    {
        $this->assertNotSystemRole($role, 'System roles cannot be edited.');

        $before = $role->getPermissionNames()->all();
        $oldName = $role->name;

        if ($request->filled('name')) {
            $role->update(['name' => $request->string('name')]);
        }
        $after = $request->validated('permissions');
        $role->syncPermissions($after);

        // Audit the real change: permission grants/revokes live in a pivot table, so
        // the model observer never sees them.
        $changes = [];
        if ($role->name !== $oldName) {
            $changes['name'] = ['old' => $oldName, 'new' => $role->name];
        }
        if ($added = array_values(array_diff($after, $before))) {
            $changes['permissions_added'] = $added;
        }
        if ($removed = array_values(array_diff($before, $after))) {
            $changes['permissions_removed'] = $removed;
        }
        if ($changes) {
            $audit->log('updated', 'Role', $role->id, $changes);
        }

        return new RoleResource($role->setAttribute('users_count', $role->users()->count()));
    }

    public function destroy(Role $role, AuditRecorder $audit): JsonResponse
    {
        $this->assertNotSystemRole($role, 'System roles cannot be deleted.');

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Reassign the users on this role before deleting it.'],
            ]);
        }

        $id = $role->id;
        $name = $role->name;
        $role->delete();

        $audit->log('deleted', 'Role', $id, ['name' => $name]);

        return response()->json(null, 204);
    }

    private function assertNotSystemRole(Role $role, string $message): void
    {
        if (PermissionCatalog::isSystemRole($role->name)) {
            throw ValidationException::withMessages(['role' => [$message]]);
        }
    }
}
