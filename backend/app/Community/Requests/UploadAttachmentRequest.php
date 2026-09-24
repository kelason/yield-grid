<?php

declare(strict_types=1);

namespace App\Community\Requests;

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
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'], // 10MB limit
            'attachable_type' => ['required', 'string', Rule::in(['thread', 'reply'])],
            'attachable_id' => ['nullable', 'integer'],
        ];
    }
}
