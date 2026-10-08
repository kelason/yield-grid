<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use App\Domain\Community\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
            'reportable_type' => ['required', 'string', Rule::in(ForumConstants::REPORTABLE_TYPES)],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => [
                'nullable',
                'string',
                'max:'.ForumConstants::REPORT_DESCRIPTION_MAX_LENGTH,
                Rule::requiredIf(fn (): bool => $this->input('reason') === ReportReason::OTHER->value),
            ],
        ];
    }
}
