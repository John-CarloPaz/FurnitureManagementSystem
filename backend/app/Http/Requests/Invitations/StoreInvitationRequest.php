<?php

namespace App\Http\Requests\Invitations;

use App\Domain\Access\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is guarded by permission:invitations.create
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => [
                'required', 'string', 'exists:roles,name',
                // Only super_admin may hand out the two privileged roles.
                Rule::notIn($this->privilegedRolesForbiddenToInviter()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with that email already exists.',
            'role.not_in' => 'You are not allowed to invite someone into that role.',
        ];
    }

    /** @return list<string> */
    private function privilegedRolesForbiddenToInviter(): array
    {
        // super_admin (holder of roles.create) can invite into any role.
        if ($this->user()?->can('roles.create')) {
            return [];
        }

        return [PermissionCatalog::SUPER_ADMIN, PermissionCatalog::ADMIN];
    }
}
