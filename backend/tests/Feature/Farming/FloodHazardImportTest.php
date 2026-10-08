<?php

declare(strict_types=1);

namespace Tests\Feature\Farming;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloodHazardImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_noah_geojson_and_reports_skips(): void
    {
        $fixture = __DIR__.'/fixtures/noah-flood-sample.geojson';

        $this->artisan('noah:import-flood-zones', ['path' => $fixture])
            ->assertSuccessful()
            ->expectsOutputToContain('Imported 3 zones (skipped_invalid: 1, skipped_unknown_class: 1)');
        $this->assertDatabaseCount('flood_hazard_zones', 3);
    }
}
