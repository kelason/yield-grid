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
            'amount' => 'nullable|numeric|min:0.01',
        ];
    }
}
