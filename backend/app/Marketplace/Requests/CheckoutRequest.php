<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
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
            // 6 digits max (mirrors the CheckoutSummary 6-char cap; max_digits
            // cannot be used since it rejects decimal points).
            'quantity_kg' => 'required|numeric|min:'.MarketplaceConstants::CHECKOUT_QUANTITY_MIN_KG.'|max:'.MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG,
            'payment_option' => 'required|in:cash,paymongo',
        ];
    }
}
