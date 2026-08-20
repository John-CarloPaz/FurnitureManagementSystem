<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', Rule::in(config('catalog.categories'))],
            'material' => ['nullable', Rule::in(config('catalog.materials'))],
            'wood_type' => ['nullable', Rule::in(config('catalog.wood_types'))],
            'finish' => ['nullable', Rule::in(config('catalog.finishes'))],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'depth_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
