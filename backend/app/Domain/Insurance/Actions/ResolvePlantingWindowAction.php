<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\PlantingWindow;

final class ResolvePlantingWindowAction
{
    /** @var array<string, PlantingWindow>|null Memoized region/program/season index. */
    private ?array $index = null;

    /**
     * Resolve the planting window for a region, falling back to NATIONAL.
     */
    public function execute(
        ?string $regionCode,
        InsuranceProgram $program,
        Season $season,
    ): ?PlantingWindow {
        $index = $this->indexed();

        if ($regionCode !== null) {
            $regional = $index[$this->key($regionCode, $program, $season)] ?? null;

            if ($regional !== null) {
                return $regional;
            }
        }

        return $index[$this->key(InsuranceConstants::NATIONAL_REGION_CODE, $program, $season)] ?? null;
    }

    /**
     * @return array<string, PlantingWindow>
     */
    private function indexed(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = [];

        foreach (PlantingWindow::all() as $window) {
            $index[$this->key($window->region_code, $window->program, $window->season)] = $window;
        }

        return $this->index = $index;
    }

    private function key(string $regionCode, InsuranceProgram $program, Season $season): string
    {
        return "{$regionCode}:{$program->value}:{$season->value}";
    }
}
