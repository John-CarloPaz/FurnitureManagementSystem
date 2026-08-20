<?php

namespace App\Http\Requests\Dss;

use Illuminate\Foundation\Http\FormRequest;

class RouteOptimizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.id' => ['required'],
            'stops.*.label' => ['nullable', 'string', 'max:255'],
            'stops.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'stops.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'start' => ['nullable', 'array'],
            'start.lat' => ['required_with:start', 'numeric', 'between:-90,90'],
            'start.lng' => ['required_with:start', 'numeric', 'between:-180,180'],
        ];
    }
}
