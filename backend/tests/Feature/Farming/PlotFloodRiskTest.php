<?php

declare(strict_types=1);

namespace Tests\Feature\Farming;

use App\Domain\Farming\Actions\AssessPlotFloodRiskAction;
use Domain\Farming\Models\Farm;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlotFloodRiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_high_for_overlapping_polygon(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $this->seedZones();

        $response = $this->actingAs($farmer)->postJson('/api/v1/plots/flood-risk/preview', [
            'coordinates' => [[4, 4], [4, 8], [8, 8], [8, 4]],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.level', 'high')
            ->assertJsonPath('data.label', 'High')
            ->assertJsonPath('data.legend_token', 'high')
            ->assertJsonPath('data.within_coverage', true)
            ->assertJsonStructure(['data' => ['level', 'label', 'legend_token', 'advice', 'within_coverage', 'assessed_at']]);
    }

    public function test_preview_rejects_self_intersecting_polygon(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);

        $response = $this->actingAs($farmer)->postJson('/api/v1/plots/flood-risk/preview', [
            'coordinates' => [[0, 0], [4, 4], [4, 0], [0, 4], [0, 0]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['coordinates']);
    }

    public function test_preview_closes_unclosed_ring(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $this->seedZones();

        $response = $this->actingAs($farmer)->postJson('/api/v1/plots/flood-risk/preview', [
            'coordinates' => [[0, 0], [0, 2], [2, 0]],
        ]);

        $response->assertOk()->assertJsonPath('data.level', 'low');
    }

    public function test_show_forbids_other_farmers_plot(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();
        $plotId = $this->createPlot($owner, $farm->id, [[0, 0], [0, 4], [4, 4], [4, 0]]);
        $intruder = User::factory()->create(['role' => UserRole::FARMER]);

        $this->actingAs($intruder)->getJson("/api/v1/plots/{$plotId}/flood-risk")->assertForbidden();
    }

    public function test_refresh_forbids_other_farmers_plot(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();
        $plotId = $this->createPlot($owner, $farm->id, [[0, 0], [0, 4], [4, 4], [4, 0]]);
        $intruder = User::factory()->create(['role' => UserRole::FARMER]);

        $this->actingAs($intruder)->postJson("/api/v1/plots/{$plotId}/flood-risk/refresh")->assertForbidden();
    }

    public function test_show_returns_unknown_for_legacy_plot(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();

        $plotId = DB::table('plots')->insertGetId([
            'farm_id' => $farm->id,
            'name' => 'Legacy Plot',
            'polygon' => DB::raw("ST_GeomFromText('POLYGON((0 0, 0 4, 4 4, 4 0, 0 0))', 4326)"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)->getJson("/api/v1/plots/{$plotId}/flood-risk")
            ->assertOk()
            ->assertJsonPath('data.level', 'unknown')
            ->assertJsonPath('data.assessed_at', null);
    }

    public function test_refresh_recomputes_and_persists(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();
        $this->seedZones();
        $plotId = $this->createPlot($owner, $farm->id, [[4, 4], [4, 8], [8, 8], [8, 4]]);
        DB::table('plots')->where('id', $plotId)->update([
            'flood_risk_level' => null,
            'flood_within_coverage' => null,
            'flood_risk_assessed_at' => null,
        ]);

        $this->actingAs($owner)->postJson("/api/v1/plots/{$plotId}/flood-risk/refresh")
            ->assertOk()
            ->assertJsonPath('data.level', 'high');

        $this->assertDatabaseHas('plots', ['id' => $plotId, 'flood_risk_level' => 'high']);
        $this->assertNotNull(DB::table('plots')->where('id', $plotId)->value('flood_risk_assessed_at'));
    }

    public function test_zones_index_returns_feature_collection(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $this->seedZones();

        $response = $this->actingAs($farmer)->getJson('/api/v1/flood-hazard-zones?bbox=-1,-1,4,4');

        $response->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(2, 'features')
            ->assertJsonPath('features.0.properties.hazard_class', 'low')
            ->assertJsonPath('features.1.properties.hazard_class', 'medium');
    }

    public function test_zones_index_caps_response_at_500(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $this->seedTinyZones(510);

        $response = $this->actingAs($farmer)->getJson('/api/v1/flood-hazard-zones?bbox=0,0,1,1');

        $response->assertOk()->assertJsonCount(500, 'features');
    }

    public function test_zones_index_rejects_oversized_bbox(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);

        $this->actingAs($farmer)->getJson('/api/v1/flood-hazard-zones?bbox=0,0,90,90')->assertStatus(422);
    }

    public function test_creating_plot_persists_flood_risk(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();
        $this->seedZones();

        $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'Risky Field',
            'soil_type' => 'loamy',
            'coordinates' => [[4, 4], [4, 8], [8, 8], [8, 4]],
        ])->assertCreated()->assertJsonPath('data.flood_risk_level', 'high');
    }

    public function test_creating_plot_without_zones_is_unknown(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();

        $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'Lonely Field',
            'soil_type' => 'loamy',
            'coordinates' => [[4, 4], [4, 8], [8, 8], [8, 4]],
        ])->assertCreated()->assertJsonPath('data.flood_risk_level', 'unknown');
    }

    public function test_creating_plot_marks_unknown_when_assessment_fails(): void
    {
        [$owner, $farm] = $this->makeFarmerWithFarm();
        // Anonymous double: the action is final, so it cannot be mocked.
        app()->bind(AssessPlotFloodRiskAction::class, fn () => new class
        {
            public function __invoke(): never
            {
                throw new RuntimeException('NOAH offline');
            }
        });

        $response = $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'Unlucky Field',
            'soil_type' => 'loamy',
            'coordinates' => [[4, 4], [4, 8], [8, 8], [8, 4]],
        ]);

        $response->assertCreated()->assertJsonPath('data.flood_risk_level', 'unknown');
        $this->assertDatabaseHas('plots', [
            'id' => $response->json('data.id'),
            'flood_risk_level' => 'unknown',
            'flood_within_coverage' => false,
        ]);
        $this->assertNotNull(DB::table('plots')->where('id', $response->json('data.id'))->value('flood_risk_assessed_at'));
    }

    /**
     * @return array{User, Farm}
     */
    private function makeFarmerWithFarm(): array
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);

        return [$farmer, $farm];
    }

    /**
     * @param  array<int, array{float, float}>  $coordinates
     */
    private function createPlot(User $owner, int $farmId, array $coordinates): int
    {
        return $this->actingAs($owner)->postJson("/api/v1/farms/{$farmId}/plots", [
            'name' => 'Test Plot',
            'soil_type' => 'loamy',
            'coordinates' => $coordinates,
        ])->assertCreated()->json('data.id');
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

    private function seedTinyZones(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $x = ($i % 30) * 0.03;
            $y = intdiv($i, 30) * 0.03;
            $x1 = $x + 0.02;
            $y1 = $y + 0.02;

            DB::insert(
                'INSERT INTO flood_hazard_zones (hazard_class, return_period_years, polygon, created_at, updated_at) VALUES (?, 5, ST_Multi(ST_GeomFromText(?, 4326)), NOW(), NOW())',
                ['low', "POLYGON(({$x} {$y}, {$x} {$y1}, {$x1} {$y1}, {$x1} {$y}, {$x} {$y}))"]
            );
        }
    }
}
