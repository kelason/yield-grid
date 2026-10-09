<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreHarvestListingRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:'.MarketplaceConstants::LISTING_TITLE_MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:'.MarketplaceConstants::LISTING_DESCRIPTION_MAX_LENGTH],
            'crop_name' => ['required', 'string', 'max:'.MarketplaceConstants::LISTING_CROP_NAME_MAX_LENGTH],
            'quantity_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::LISTING_QUANTITY_MIN_KG, 'max:'.MarketplaceConstants::LISTING_QUANTITY_MAX_KG],
            'price_per_kg' => ['required', 'numeric', 'min:'.MarketplaceConstants::LISTING_PRICE_MIN, 'max:'.MarketplaceConstants::LISTING_PRICE_MAX],
            'estimated_harvest_date' => ['required_if:is_harvest_available,false', 'nullable', 'date'],
            'shelf_life_days' => ['required', 'integer', 'min:'.MarketplaceConstants::LISTING_SHELF_LIFE_MIN_DAYS, 'max:'.MarketplaceConstants::LISTING_SHELF_LIFE_MAX_DAYS],
            'is_harvest_available' => ['required', 'boolean'],
            'farm_id' => ['nullable', 'integer', Rule::exists('farms', 'id')->where('user_id', (int) $this->user()?->id)],
            'plot_id' => ['nullable', 'integer', Rule::exists('plots', 'id')->where('farm_id', $this->input('farm_id'))],
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

        $validator->after(function (Validator $validator): void {
            if ($this->filled('plot_id') && ! $this->filled('farm_id')) {
                $validator->errors()->add('plot_id', 'A plot cannot be linked without its farm.');
            }
        });
    }
}
