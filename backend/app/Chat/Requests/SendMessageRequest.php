<?php

declare(strict_types=1);

namespace App\Chat\Requests;

use App\Constants\ChatConstants;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:'.ChatConstants::MESSAGE_MAX_LENGTH,
            ],
        ];
    }
}
