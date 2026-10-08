<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminIssueFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('search'))) {
            $this->merge(['search' => trim((string) $this->input('search'))]);
        }

        if ($this->input('search') === '') {
            $this->merge(['search' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'category' => ['nullable', Rule::enum(IssueCategory::class)],
            'search' => ['nullable', 'string', 'max:'.AdminConstants::ADMIN_SEARCH_MAX_LENGTH],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
