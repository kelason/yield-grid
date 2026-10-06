<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Actions;

use Domain\Farming\Models\Plot;

interface BuildsAnalysisContext
{
    /**
     * @param  array<string, mixed>|null  $preferences
     * @return array{subtypes?: list<string>, irrigation?: string, goal?: string, season: string, date: string, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>, previous_crops?: list<string>}
     */
    public function execute(Plot $plot, ?array $preferences): array;
}
