<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDemandRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'crop_name' => ['required', 'string', 'max:100'],
            'quantity_kg' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'target_price_per_kg' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'needed_by_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:needed_by_date'],
            'address_id' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $quantity = $this->input('quantity_kg');
            $price = $this->input('target_price_per_kg');

            if (! is_numeric($quantity) || ! is_numeric($price)) {
                return;
            }

            if ((float) $quantity * (float) $price > MarketplaceConstants::ORDER_TOTAL_MAX) {
                $validator->errors()->add(
                    'quantity_kg',
                    'The combined quantity and target price exceed the maximum order total of ₱'.number_format(MarketplaceConstants::ORDER_TOTAL_MAX, 2).'.'
                );
            }
        });
    }
}
