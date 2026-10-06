<?php

declare(strict_types=1);

namespace App\CropRecommendation\Controllers;

use App\Domain\CropRecommendation\Enums\AnalysisGoal;
use App\Domain\CropRecommendation\Enums\IrrigationLevel;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CropTaxonomyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'types' => CropTaxonomy::tree(),
                'crops' => CropTaxonomy::cropList(),
                'irrigation_levels' => array_map(
                    fn (IrrigationLevel $level): array => ['value' => $level->value, 'label' => $level->label()],
                    IrrigationLevel::cases()
                ),
                'goals' => array_map(
                    fn (AnalysisGoal $goal): array => ['value' => $goal->value, 'label' => $goal->label()],
                    AnalysisGoal::cases()
                ),
            ],
        ]);
    }
}
