<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateParentThread($validator);
        });
    }

    private function validateParentThread(Validator $validator): void
    {
        $parentId = $this->input('parent_id');

        if ($parentId === null || $parentId === '') {
            return;
        }

        $thread = $this->route('thread');
        $parent = ForumReply::whereKey((int) $parentId)->first();

        if (! $parent instanceof ForumReply) {
            return;
        }

        if (! $thread instanceof ForumThread || $parent->thread_id !== $thread->id) {
            $validator->errors()->add('parent_id', 'The parent reply must belong to the same thread.');
        }
    }
}
