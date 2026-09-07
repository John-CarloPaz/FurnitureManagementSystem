<?php

namespace App\Http\Requests\Roles;

use App\Domain\Access\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is guarded by permission:roles.update
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::permissionNames())],
        ];
    }
}
