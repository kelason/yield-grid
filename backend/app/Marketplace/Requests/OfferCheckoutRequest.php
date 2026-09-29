<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OfferCheckoutRequest extends FormRequest
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
            'payment_option' => 'required|in:cash,paymongo',
        ];
    }
}
