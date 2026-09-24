<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policies will handle actual authorization
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:'.ForumConstants::TITLE_MIN_LENGTH, 'max:'.ForumConstants::TITLE_MAX_LENGTH],
            'body' => ['sometimes', 'required', 'string', 'min:'.ForumConstants::BODY_MIN_LENGTH, 'max:'.ForumConstants::BODY_MAX_LENGTH],
            'tag_ids' => ['nullable', 'array', 'max:'.ForumConstants::MAX_TAGS_PER_THREAD],
            'tag_ids.*' => ['integer', 'exists:forum_tags,id'],
        ];
    }
}
