<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\ReportingConstants;
use App\Domain\Shared\Enums\ContentReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DecideContentReportRequest extends FormRequest
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

        if ($this->input('outcome') === '') {
            $this->merge(['outcome' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(ContentReportStatus::class)],
            'outcome' => [
                'nullable',
                'string',
                Rule::in([ReportingConstants::OUTCOME_HIDDEN, ReportingConstants::OUTCOME_NO_ACTION]),
                Rule::requiredIf(fn (): bool => $this->input('status') === ContentReportStatus::RESOLVED->value),
                Rule::prohibitedIf(fn (): bool => $this->input('status') !== ContentReportStatus::RESOLVED->value),
            ],
            'note' => ['required', 'string', 'min:1', 'max:'.ReportingConstants::DECISION_NOTE_MAX_LENGTH],
            'expected_version' => ['required', 'integer', 'min:1', 'max:'.ReportingConstants::EXPECTED_VERSION_MAX],
        ];
    }
}
