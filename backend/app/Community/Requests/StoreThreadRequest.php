<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:'.ForumConstants::TITLE_MIN_LENGTH, 'max:'.ForumConstants::TITLE_MAX_LENGTH],
            'body' => ['required', 'string', 'min:'.ForumConstants::BODY_MIN_LENGTH, 'max:'.ForumConstants::BODY_MAX_LENGTH],
            'category_id' => ['required', 'integer', 'exists:forum_categories,id'],
            'tag_ids' => ['nullable', 'array', 'max:'.ForumConstants::MAX_TAGS_PER_THREAD],
            'tag_ids.*' => ['integer', 'exists:forum_tags,id'],
            'is_anonymous' => ['nullable', 'boolean'],
        ];
    }
}
