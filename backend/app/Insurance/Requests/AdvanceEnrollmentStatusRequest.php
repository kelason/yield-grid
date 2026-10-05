<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Domain\Insurance\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdvanceEnrollmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role->value === 'farmer';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(EnrollmentStatus::class)],
        ];
    }
}
