<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Constants\InsuranceConstants;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePolicyDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role->value === 'farmer';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cic_number' => ['required', 'string', 'max:'.InsuranceConstants::CIC_NUMBER_MAX_LENGTH],
            'coverage_amount_php' => ['nullable', 'numeric', 'min:0', 'max:'.InsuranceConstants::COVERAGE_AMOUNT_MAX],
            'enrolled_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:enrolled_at'],
        ];
    }
}
