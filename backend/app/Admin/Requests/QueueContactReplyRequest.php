<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\ContactConstants;
use Illuminate\Foundation\Http\FormRequest;

final class QueueContactReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim((string) $this->input('body'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:'.ContactConstants::REPLY_BODY_MAX_LENGTH],
            'client_request_id' => ['required', 'string', 'uuid'],
        ];
    }
}
