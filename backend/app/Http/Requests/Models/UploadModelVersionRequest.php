<?php

namespace App\Http\Requests\Models;

use Illuminate\Foundation\Http\FormRequest;

class UploadModelVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Client-provided assets only — .glb / .obj, up to 20MB.
            'file' => ['required', 'file', 'extensions:glb,obj', 'max:20480'],
            'change_log' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
