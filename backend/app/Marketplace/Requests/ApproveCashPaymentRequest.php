<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;

final class ApproveCashPaymentRequest extends FormRequest
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
            'type' => 'required|in:partial,full',
            // 8 digits max (mirrors the approval modal 8-char cap).
            'amount' => 'nullable|numeric|min:'.MarketplaceConstants::CASH_APPROVAL_AMOUNT_MIN.'|max:'.MarketplaceConstants::CASH_APPROVAL_AMOUNT_MAX,
        ];
    }
}
