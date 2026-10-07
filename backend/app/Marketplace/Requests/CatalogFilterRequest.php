<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CatalogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $amount = ['nullable', 'numeric', 'min:'.MarketplaceConstants::FILTER_PRICE_MIN, 'max:'.MarketplaceConstants::ORDER_TOTAL_MAX];

        return [
            'crop' => ['nullable', 'string', 'min:'.MarketplaceConstants::FILTER_PRICE_MIN, 'max:'.MarketplaceConstants::DEMAND_CROP_NAME_MAX_LENGTH],
            'search' => ['nullable', 'string', 'min:'.MarketplaceConstants::FILTER_PRICE_MIN, 'max:'.MarketplaceConstants::DEMAND_CROP_NAME_MAX_LENGTH],
            'min_price' => $amount,
            'max_price' => $amount,
            'min_budget' => $amount,
            'max_budget' => $amount,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            foreach (['price', 'budget'] as $field) {
                $min = $data['min_'.$field] ?? null;
                $max = $data['max_'.$field] ?? null;
                if (is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
                    $validator->errors()->add('max_'.$field, 'The maximum must be greater than or equal to the minimum.');
                }
            }
        });
    }
}
