<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

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
            'amount' => 'nullable|numeric|min:0.01|max:99999999',
        ];
    }
}
