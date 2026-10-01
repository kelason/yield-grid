<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\CashPaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required', Rule::enum(CashPaymentType::class)],
            // 8 digits max (mirrors the approval modal 8-char cap).
            'amount' => 'nullable|numeric|min:'.MarketplaceConstants::CASH_APPROVAL_AMOUNT_MIN.'|max:'.MarketplaceConstants::CASH_APPROVAL_AMOUNT_MAX,
        ];
    }
}
