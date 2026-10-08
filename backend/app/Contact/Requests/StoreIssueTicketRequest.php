<?php

declare(strict_types=1);

namespace App\Contact\Requests;

use App\Constants\IssueConstants;
use App\Domain\Contact\Enums\IssueCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreIssueTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['subject', 'description', 'page_path'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        if ($this->input('page_path') === '') {
            $this->merge(['page_path' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::enum(IssueCategory::class)],
            'subject' => ['required', 'string', 'min:1', 'max:'.IssueConstants::SUBJECT_MAX_LENGTH],
            'description' => ['required', 'string', 'min:1', 'max:'.IssueConstants::DESCRIPTION_MAX_LENGTH],
            'page_path' => ['nullable', 'string', 'min:1', 'max:'.IssueConstants::PAGE_PATH_MAX_LENGTH, $this->pagePathRule()],
            'client_request_id' => ['required', 'string', 'uuid'],
        ];
    }

    private function pagePathRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! $this->isSafeRelativePath($value)) {
                $fail('The page path must be a relative path like /dashboard/issues.');
            }
        };
    }

    private function isSafeRelativePath(string $path): bool
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }

        return preg_match('~[?#\s\\\\\x00-\x1F\x7F]|//~', $path) !== 1;
    }
}
