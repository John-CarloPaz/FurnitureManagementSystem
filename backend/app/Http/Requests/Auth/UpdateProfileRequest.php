<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any authenticated user may edit their own profile
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes', 'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->user()?->id),
            ],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
            'current_password' => ['required_with:password', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'That username is already taken.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores.',
        ];
    }
}
