<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\ClaimStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AdvanceClaimStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(ClaimStatus::class)],
            'paid_amount_php' => ['nullable', 'numeric', 'gt:0', 'max:'.InsuranceConstants::COVERAGE_AMOUNT_MAX],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('paid_amount_php') !== null && $this->input('status') !== ClaimStatus::PAID->value) {
                $validator->errors()->add('paid_amount_php', 'A payout amount is only accepted when marking the claim paid.');
            }
        });
    }
}
