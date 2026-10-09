<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use Domain\Farming\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminVerificationFilterRequest extends FormRequest
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
            'scope' => ['nullable', Rule::in([AdminConstants::VERIFICATION_SCOPE_FARMS, AdminConstants::VERIFICATION_SCOPE_PLOTS])],
            'status' => ['nullable', Rule::in(VerificationStatus::values())],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
