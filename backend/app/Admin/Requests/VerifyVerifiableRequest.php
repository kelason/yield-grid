<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\FarmingConstants;
use Domain\Farming\Enums\VerificationMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyVerifiableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim((string) $this->input('note'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(VerificationMethod::values())],
            'note' => ['nullable', 'string', 'max:'.FarmingConstants::VERIFICATION_NOTE_MAX_LENGTH],
        ];
    }
}
