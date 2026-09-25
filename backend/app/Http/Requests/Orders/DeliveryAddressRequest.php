<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:80'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'province_name' => ['required', 'string', 'max:120'],
            'city_code' => ['nullable', 'string', 'max:20'],
            'city_name' => ['required', 'string', 'max:120'],
            'barangay_code' => ['nullable', 'string', 'max:20'],
            'barangay_name' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ];
    }
}
