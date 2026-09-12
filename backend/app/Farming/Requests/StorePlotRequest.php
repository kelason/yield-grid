<?php

namespace App\Farming\Requests;

use Domain\Farming\Enums\SoilType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role->value === 'farmer';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'soil_type' => ['nullable', 'string', Rule::enum(SoilType::class)],
            'coordinates' => ['required', 'array', 'min:3'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.*' => ['required', 'numeric'], // [lng, lat]
        ];
    }
}
