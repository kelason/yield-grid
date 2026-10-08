<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\IssueConstants;
use App\Domain\Contact\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionIssueTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('resolution'))) {
            $this->merge(['resolution' => trim((string) $this->input('resolution'))]);
        }

        if ($this->input('resolution') === '') {
            $this->merge(['resolution' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(IssueStatus::class)],
            'resolution' => [
                'nullable',
                'string',
                'min:1',
                'max:'.IssueConstants::RESOLUTION_MAX_LENGTH,
                Rule::requiredIf(fn (): bool => $this->wantsClosingStatus()),
                Rule::prohibitedIf(fn (): bool => ! $this->wantsClosingStatus()),
            ],
            'expected_version' => ['required', 'integer', 'min:1', 'max:'.IssueConstants::EXPECTED_VERSION_MAX],
        ];
    }

    private function wantsClosingStatus(): bool
    {
        return in_array($this->input('status'), [IssueStatus::RESOLVED->value, IssueStatus::CLOSED->value], true);
    }
}
