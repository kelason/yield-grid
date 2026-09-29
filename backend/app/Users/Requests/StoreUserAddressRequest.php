<?php

declare(strict_types=1);

namespace App\Users\Requests;

use App\Infrastructure\Services\PsgcService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUserAddressRequest extends FormRequest
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
        return UserAddressRules::rules();
    }

    /**
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        UserAddressRules::validateSemantics($validator, $this->validationData(), app(PsgcService::class));
    }
}
