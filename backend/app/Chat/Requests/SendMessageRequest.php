<?php

declare(strict_types=1);

namespace App\Chat\Requests;

use App\Constants\ChatConstants;
use Closure;
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
                function (string $attribute, mixed $value, Closure $fail): void {
                    $words = preg_split('/\s+/', trim((string) $value)) ?: [];
                    if (count($words) > ChatConstants::MESSAGE_MAX_WORDS) {
                        $fail('The '.$attribute.' may not be greater than '.ChatConstants::MESSAGE_MAX_WORDS.' words.');
                    }
                },
            ],
        ];
    }
}
