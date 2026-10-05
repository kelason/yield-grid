<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\LossCause;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClaimRequest extends FormRequest
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
            'loss_date' => ['required', 'date', 'before_or_equal:today'],
            'cause' => ['required', 'string', Rule::enum(LossCause::class)],
            'description' => ['nullable', 'string', 'max:'.InsuranceConstants::CLAIM_DESCRIPTION_MAX_LENGTH],
        ];
    }
}
