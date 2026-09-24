<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Domain\Community\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(['thread', 'reply'])],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
