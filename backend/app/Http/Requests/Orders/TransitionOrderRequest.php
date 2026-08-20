<?php

namespace App\Http\Requests\Orders;

use App\Domain\Orders\Enums\OrderState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to' => ['required', Rule::enum(OrderState::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
