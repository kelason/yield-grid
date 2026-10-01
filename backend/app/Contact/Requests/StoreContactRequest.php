<?php

namespace App\Contact\Requests;

use App\Constants\ContactConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:'.ContactConstants::NAME_MAX_LENGTH],
            'email' => ['required', 'email', 'max:'.ContactConstants::EMAIL_MAX_LENGTH],
            'subject' => ['nullable', 'string', 'max:'.ContactConstants::SUBJECT_MAX_LENGTH],
            'message' => ['required', 'string', 'max:'.ContactConstants::MESSAGE_MAX_LENGTH],
        ];
    }
}
