<?php

declare(strict_types=1);

namespace App\Admin\Requests;

use App\Constants\AdminConstants;
use Illuminate\Foundation\Http\FormRequest;

final class AdminReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reason'))) {
            $this->merge(['reason' => trim((string) $this->input('reason'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:1', 'max:'.AdminConstants::SUSPENSION_REASON_MAX_LENGTH],
        ];
    }
}
