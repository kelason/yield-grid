<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator as ConcreteValidator;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.MarketplaceConstants::CONTRACT_TITLE_MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:'.MarketplaceConstants::CONTRACT_DESCRIPTION_MAX_LENGTH],
            'quantity_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::CONTRACT_QUANTITY_MIN_KG, 'max:'.MarketplaceConstants::CONTRACT_QUANTITY_MAX_KG],
            'price_per_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::CONTRACT_PRICE_MIN, 'max:'.MarketplaceConstants::CONTRACT_PRICE_MAX],
            'estimated_harvest_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:estimated_harvest_date'],
        ];
    }

    public function withValidator(ConcreteValidator $validator): void
    {
        $validator->after(function (ConcreteValidator $validator): void {
            $quantity = $this->input('quantity_kg');
            $price = $this->input('price_per_kg');

            if (! is_numeric($quantity) || ! is_numeric($price)) {
                return;
            }

            if ((float) $quantity * (float) $price > MarketplaceConstants::ORDER_TOTAL_MAX) {
                $validator->errors()->add(
                    'quantity_kg',
                    'The combined quantity and price exceed the maximum order total of ₱'.number_format(MarketplaceConstants::ORDER_TOTAL_MAX, 2).'.'
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        // Log failed field names only — never request payloads (PII).
        Log::error('Publish contract validation failed.', [
            'fields' => array_keys($validator->errors()->toArray()),
        ]);
        parent::failedValidation($validator);
    }
}
