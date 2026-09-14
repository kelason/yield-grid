<?php

namespace App\Farming\Requests;

use Domain\Farming\Enums\SoilType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmRequest extends FormRequest
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
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
