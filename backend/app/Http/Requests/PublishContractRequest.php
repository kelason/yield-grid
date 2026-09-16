<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PublishContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity_kg' => ['required', 'numeric', 'min:1'],
            'price_per_kg' => ['required', 'numeric', 'min:0.01'],
            'estimated_harvest_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:estimated_harvest_date'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Illuminate\Support\Facades\Log::error('Validation Failed Data:', $this->all());
        \Illuminate\Support\Facades\Log::error('Validation Failed Errors:', $validator->errors()->toArray());
        parent::failedValidation($validator);
    }
}
