<?php

declare(strict_types=1);

namespace App\Users\Requests;

use App\Constants\LocaleConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserLocaleRequest extends FormRequest
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
            'locale' => ['required', Rule::in(LocaleConstants::SUPPORTED)],
        ];
    }
}
