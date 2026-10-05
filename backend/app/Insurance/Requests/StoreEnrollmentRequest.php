<?php

declare(strict_types=1);

namespace App\Insurance\Requests;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEnrollmentRequest extends FormRequest
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
            'plot_id' => ['nullable', 'integer', 'exists:plots,id'],
            'program' => ['required', 'string', Rule::enum(InsuranceProgram::class)],
            'season' => ['required', 'string', Rule::enum(Season::class)],
            'season_year' => [
                'required',
                'integer',
                'min:'.InsuranceConstants::SEASON_YEAR_MIN,
                'max:'.InsuranceConstants::SEASON_YEAR_MAX,
            ],
            'notes' => ['nullable', 'string', 'max:'.InsuranceConstants::NOTES_MAX_LENGTH],
        ];
    }
}
