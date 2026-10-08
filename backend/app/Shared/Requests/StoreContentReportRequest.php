<?php

declare(strict_types=1);

namespace App\Shared\Requests;

use App\Constants\ReportingConstants;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ReportTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentReportRequest extends FormRequest
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
            'reportable_type' => ['required', 'string', Rule::enum(ReportTargetType::class)],
            'reportable_id' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'reason' => ['required', 'string', Rule::enum(ContentReportReason::class)],
            'description' => [
                'nullable',
                'string',
                'max:'.ReportingConstants::DESCRIPTION_MAX_LENGTH,
                Rule::requiredIf(fn (): bool => $this->input('reason') === ContentReportReason::OTHER->value),
            ],
        ];
    }
}
