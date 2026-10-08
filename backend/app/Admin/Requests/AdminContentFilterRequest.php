<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use App\Constants\ReportingConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminContentFilterRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:'.AdminConstants::ADMIN_SEARCH_MAX_LENGTH],
            'visibility' => ['nullable', Rule::in([
                ReportingConstants::VISIBILITY_VISIBLE,
                ReportingConstants::VISIBILITY_HIDDEN,
            ])],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
