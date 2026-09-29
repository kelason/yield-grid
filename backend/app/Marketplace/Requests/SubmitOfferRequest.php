<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::OFFER_QUANTITY_MIN_KG, 'max:'.MarketplaceConstants::OFFER_QUANTITY_MAX_KG],
            'price_per_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::OFFER_PRICE_MIN, 'max:'.MarketplaceConstants::OFFER_PRICE_MAX],
            'message' => ['nullable', 'string', 'max:'.MarketplaceConstants::OFFER_MESSAGE_MAX_LENGTH],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
}
