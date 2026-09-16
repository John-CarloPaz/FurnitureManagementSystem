<?php

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class RecordQualityInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission checked in the controller (manufacturing.verify)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'passed' => ['required', 'boolean'],
            'reason' => ['required_if:passed,false,0', 'nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return ['reason.required_if' => 'Add a reason describing why the item failed QC.'];
    }
}
