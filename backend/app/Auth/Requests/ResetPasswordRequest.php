<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\Constants\AuthConstants;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:'.AuthConstants::RESET_TOKEN_MAX_LENGTH],
            'email' => ['required', 'email', 'max:'.AuthConstants::EMAIL_MAX_LENGTH],
            'password' => ['required', 'confirmed', Password::defaults(), 'max:'.AuthConstants::PASSWORD_MAX_LENGTH],
        ];
    }
}
