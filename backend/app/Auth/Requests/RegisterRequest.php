<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\Constants\AuthConstants;
use App\Constants\LocaleConstants;
use App\Infrastructure\Services\PsgcService;
use App\Users\Requests\UserAddressRules;
use Domain\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
        return array_merge([
            'name' => ['required', 'string', 'max:'.AuthConstants::NAME_MAX_LENGTH],
            'email' => ['required', 'string', 'email', 'max:'.AuthConstants::EMAIL_MAX_LENGTH, 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults(), 'max:'.AuthConstants::PASSWORD_MAX_LENGTH],
            'role' => ['required', Rule::in([UserRole::FARMER->value, UserRole::BUYER->value])],
            'locale' => ['nullable', Rule::in(LocaleConstants::SUPPORTED)],
            'address' => ['nullable', 'array'],
        ], UserAddressRules::rules('address', true));
    }

    /**
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $address = $this->input('address');
        if (! is_array($address)) {
            return;
        }

        UserAddressRules::validateSemantics($validator, $address, app(PsgcService::class), 'address');
    }
}
