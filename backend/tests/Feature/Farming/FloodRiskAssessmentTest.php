<?php

declare(strict_types=1);

namespace Tests\Feature\Farming;

use App\Constants\FloodRiskConstants;
use App\Domain\Farming\Actions\AssessPlotFloodRiskAction;
use App\Domain\Farming\Enums\FloodRiskLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FloodRiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_highest_touching_class_wins(): void
    {
        $this->seedZones();

        $result = (new AssessPlotFloodRiskAction)([[4, 4], [4, 8], [8, 8], [8, 4]]);

        $this->assertSame(FloodRiskLevel::HIGH, $result->level);
        $this->assertTrue($result->withinCoverage);
    }

    public function test_plot_outside_coverage_is_safe_with_note(): void
    {
        $this->seedZones();

        $result = (new AssessPlotFloodRiskAction)([[20, 20], [20, 24], [24, 24], [24, 20]]);

        $this->assertSame(FloodRiskLevel::SAFE, $result->level);
        $this->assertFalse($result->withinCoverage);
        $this->assertContains(FloodRiskConstants::OUTSIDE_COVERAGE_NOTE, $result->advice);
    }

    public function test_empty_zones_table_is_unknown(): void
    {
        $result = (new AssessPlotFloodRiskAction)([[4, 4], [4, 8], [8, 8], [8, 4]]);

        $this->assertSame(FloodRiskLevel::UNKNOWN, $result->level);
    }

    private function seedZones(): void
    {
        $zones = [
            ['low', 'POLYGON((0 0, 0 4, 4 4, 4 0, 0 0))'],
            ['medium', 'POLYGON((2 2, 2 6, 6 6, 6 2, 2 2))'],
            ['high', 'POLYGON((5 5, 5 9, 9 9, 9 5, 5 5))'],
        ];

        foreach ($zones as [$class, $wkt]) {
            DB::insert(
                'INSERT INTO flood_hazard_zones (hazard_class, return_period_years, polygon, created_at, updated_at) VALUES (?, 5, ST_Multi(ST_GeomFromText(?, 4326)), NOW(), NOW())',
                [$class, $wkt]
            );
        }
    }
}
