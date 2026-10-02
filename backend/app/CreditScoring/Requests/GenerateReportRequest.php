<?php

declare(strict_types=1);

namespace App\CreditScoring\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via policy in controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
