<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContactInboxFilterRequest extends FormRequest
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
            'status' => ['nullable', Rule::in([
                ContactStatus::UNREAD->value,
                ContactStatus::READ->value,
                ContactStatus::REPLIED->value,
                ContactStatus::CLOSED->value,
            ])],
            'delivery' => ['nullable', Rule::in([
                ReplyDeliveryStatus::QUEUED->value,
                ReplyDeliveryStatus::SENDING->value,
                ReplyDeliveryStatus::SENT->value,
                ReplyDeliveryStatus::FAILED->value,
            ])],
            'search' => ['nullable', 'string', 'max:'.AdminConstants::ADMIN_SEARCH_MAX_LENGTH],
            'page' => ['nullable', 'integer', 'min:'.PaginationConstants::PAGE_MIN, 'max:'.PaginationConstants::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:'.PaginationConstants::PER_PAGE_MIN, 'max:'.PaginationConstants::PER_PAGE_MAX],
        ];
    }
}
