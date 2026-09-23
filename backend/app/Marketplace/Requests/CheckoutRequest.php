<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

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
            'quantity_kg' => 'required|numeric|min:1',
            'payment_option' => 'required|in:cash,paymongo',
        ];
    }
}
