<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\PlantingWindow;

final class ResolvePlantingWindowAction
{
    /**
     * Resolve the planting window for a region, falling back to NATIONAL.
     */
    public function execute(
        ?string $regionCode,
        InsuranceProgram $program,
        Season $season,
    ): ?PlantingWindow {
        if ($regionCode !== null) {
            $regional = PlantingWindow::where('region_code', $regionCode)
                ->where('program', $program)
                ->where('season', $season)
                ->first();

            if ($regional !== null) {
                return $regional;
            }
        }

        return PlantingWindow::where('region_code', InsuranceConstants::NATIONAL_REGION_CODE)
            ->where('program', $program)
            ->where('season', $season)
            ->first();
    }
}
