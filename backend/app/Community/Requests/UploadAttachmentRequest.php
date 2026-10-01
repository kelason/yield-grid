<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Constants\ForumConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:'.ForumConstants::ATTACHMENT_ALLOWED_MIMES, 'max:'.ForumConstants::ATTACHMENT_MAX_SIZE_KB],
            'attachable_type' => ['required', 'string', Rule::in(ForumConstants::ATTACHABLE_TYPES)],
            'attachable_id' => ['nullable', 'integer'],
        ];
    }
}
