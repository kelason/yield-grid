<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\RsbsaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertInsuranceProfileRequest extends FormRequest
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
            'rsbsa_number' => ['nullable', 'string', 'max:'.InsuranceConstants::RSBSA_NUMBER_MAX_LENGTH],
            'rsbsa_status' => ['required', 'string', Rule::enum(RsbsaStatus::class)],
        ];
    }
}
