<?php

namespace Tests\Feature;

use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use Domain\Users\Models\User;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_analyze_plot_job(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot A',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 10.5
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/plots/{$plot->id}/analyze", []);

        $response->assertStatus(202);

        Queue::assertPushed(AnalyzePlotJob::class);
    }

    public function test_it_returns_recommendations_with_location_metadata(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create([
            'user_id' => $user->id,
            'name' => 'Tropical Farm',
            'city' => 'Davao City',
            'state' => 'Davao del Sur',
            'country' => 'Philippines',
        ]);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Rice Field 1',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/plots/{$plot->id}/recommendations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['plot_name', 'city', 'state', 'country']
            ])
            ->assertJsonPath('meta.city', 'Davao City')
            ->assertJsonPath('meta.country', 'Philippines');
    }

    public function test_it_throttles_rapid_concurrent_analyze_requests_for_same_plot(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot B',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0
        ]);

        $first = $this->actingAs($user)->postJson("/api/v1/plots/{$plot->id}/analyze");
        $first->assertStatus(202);

        $second = $this->actingAs($user)->postJson("/api/v1/plots/{$plot->id}/analyze");
        $second->assertStatus(429)
               ->assertJsonPath('message', 'An analysis is already underway for this plot. Please wait a moment.');
    }

    public function test_agromonitoring_service_caches_weather_and_soil_data(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot C',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 1.5
        ]);

        // Pre-populate cache
        \Illuminate\Support\Facades\Cache::put("agromonitoring_plot_data_{$plot->id}", [
            'weather' => ['main' => ['temp' => 300, 'humidity' => 70]],
            'soil' => ['moisture' => 0.35, 't0' => 299],
        ], 3600);

        // Http should not be called since cache is warm
        \Illuminate\Support\Facades\Http::fake();

        $service = app(\App\Domain\CropRecommendation\Services\AgroMonitoringService::class);
        $data = $service->getPlotData($plot);

        $this->assertEquals(300, $data['weather']['main']['temp']);
        $this->assertEquals(0.35, $data['soil']['moisture']);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
