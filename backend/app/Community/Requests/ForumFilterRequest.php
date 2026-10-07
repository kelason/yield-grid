<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use Illuminate\Foundation\Http\FormRequest;

final class ForumFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'min:'.ForumConstants::SEARCH_MIN_LENGTH, 'max:'.ForumConstants::SEARCH_MAX_LENGTH]];
    }
}
