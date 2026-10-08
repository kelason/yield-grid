<?php

declare(strict_types=1);

namespace App\Contact\Requests;

use App\Constants\PaginationConstants;
use App\Domain\Contact\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IssueTicketFilterRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
