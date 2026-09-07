<?php

namespace App\Http\Requests\Roles;

use App\Domain\Access\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is guarded by permission:roles.create
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', 'unique:roles,name'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::permissionNames())],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'A role with that name already exists.'];
    }
}
