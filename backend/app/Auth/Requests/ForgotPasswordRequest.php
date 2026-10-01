<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\Constants\AuthConstants;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:'.AuthConstants::EMAIL_MAX_LENGTH],
        ];
    }
}
