<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\Constants\AuthConstants;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:'.AuthConstants::EMAIL_MAX_LENGTH],
            'password' => ['required', 'string', 'min:'.AuthConstants::LOGIN_PASSWORD_MIN_LENGTH, 'max:'.AuthConstants::PASSWORD_MAX_LENGTH],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}
