<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class GenerateModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policy checked in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Optional — omit to re-use the last photo for this product.
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
