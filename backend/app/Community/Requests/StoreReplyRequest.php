<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:'.ForumConstants::REPLY_MIN_LENGTH, 'max:'.ForumConstants::REPLY_MAX_LENGTH],
            'parent_id' => ['nullable', 'integer', 'exists:forum_replies,id'],
            'is_anonymous' => ['nullable', 'boolean'],
        ];
    }
}
