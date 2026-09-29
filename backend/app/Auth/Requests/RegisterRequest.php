<?php

namespace App\Auth\Requests;

use App\Infrastructure\Services\PsgcService;
use App\Users\Requests\UserAddressRules;
use Illuminate\Foundation\Http\FormRequest;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:farmer,buyer'],
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
