<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\PaginationConstants;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminReportFilterRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ContentReportStatus::class)],
            'type' => ['nullable', Rule::enum(ReportTargetType::class)],
            'reason' => ['nullable', Rule::enum(ContentReportReason::class)],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
