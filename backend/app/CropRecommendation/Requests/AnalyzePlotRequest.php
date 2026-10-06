<?php

declare(strict_types=1);

namespace App\CropRecommendation\Requests;

use App\Domain\CropRecommendation\Enums\AnalysisGoal;
use App\Domain\CropRecommendation\Enums\IrrigationLevel;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzePlotRequest extends FormRequest
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
            'produce_types' => ['sometimes', 'nullable', 'array', 'max:'.count(CropTaxonomy::typeSlugs())],
            'produce_types.*' => ['string', Rule::in(CropTaxonomy::typeSlugs())],
            'subtypes' => ['sometimes', 'nullable', 'array', 'max:'.count(CropTaxonomy::subtypeSlugs())],
            'subtypes.*' => ['string', Rule::in(CropTaxonomy::subtypeSlugs())],
            'irrigation' => ['nullable', Rule::enum(IrrigationLevel::class)],
            'goal' => ['nullable', Rule::enum(AnalysisGoal::class)],
        ];
    }
}
