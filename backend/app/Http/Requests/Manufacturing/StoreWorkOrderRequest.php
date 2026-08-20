<?php

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after_or_equal:scheduled_start'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
