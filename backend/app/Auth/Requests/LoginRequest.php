<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\Constants\AuthConstants;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'password' => ['required'],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}
