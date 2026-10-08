<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use Domain\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminUserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $suspended = $this->input('suspended');

        if (is_string($suspended)) {
            $normalized = filter_var($suspended, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($normalized !== null) {
                $this->merge(['suspended' => $normalized]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:'.AdminConstants::ADMIN_SEARCH_MAX_LENGTH],
            'role' => ['nullable', Rule::in([UserRole::FARMER->value, UserRole::BUYER->value, UserRole::ADMIN->value, AdminConstants::ADMIN_USER_ROLE_MEMBERS])],
            'suspended' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
