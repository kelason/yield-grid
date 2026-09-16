<?php

namespace Tests\Feature;

use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Domain\CropRecommendation\Services\AgroMonitoringService;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
            'calculated_area' => 10.5,
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
                'meta' => ['plot_name', 'city', 'state', 'country'],
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
            'calculated_area' => 2.0,
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
            'calculated_area' => 1.5,
        ]);

        // Pre-populate cache
        Cache::put("agromonitoring_plot_data_{$plot->id}", [
            'weather' => ['main' => ['temp' => 300, 'humidity' => 70]],
            'soil' => ['moisture' => 0.35, 't0' => 299],
        ], 3600);

        // Http should not be called since cache is warm
        Http::fake();

        $service = app(AgroMonitoringService::class);
        $data = $service->getPlotData($plot);

        $this->assertEquals(300, $data['weather']['main']['temp']);
        $this->assertEquals(0.35, $data['soil']['moisture']);
        Http::assertNothingSent();
    }

    public function test_farmer_cannot_view_recommendations_of_another_farmers_plot(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);
        $otherFarmer = User::factory()->create(['role' => 'farmer']);

        $farm = Farm::create(['user_id' => $owner->id, 'name' => 'Owner Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Owner Plot',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        $response = $this->actingAs($otherFarmer)
            ->getJson("/api/v1/plots/{$plot->id}/recommendations");

        $response->assertStatus(403);
    }

    public function test_farmer_cannot_trigger_analysis_on_another_farmers_plot(): void
    {
        Queue::fake();

        $owner = User::factory()->create(['role' => 'farmer']);
        $otherFarmer = User::factory()->create(['role' => 'farmer']);

        $farm = Farm::create(['user_id' => $owner->id, 'name' => 'Owner Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Owner Plot',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        $response = $this->actingAs($otherFarmer)
            ->postJson("/api/v1/plots/{$plot->id}/analyze");

        $response->assertStatus(403);
        Queue::assertNothingPushed();
    }

    public function test_farmer_cannot_update_recommendation_status_of_another_farmers_plot(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);
        $otherFarmer = User::factory()->create(['role' => 'farmer']);

        $farm = Farm::create(['user_id' => $owner->id, 'name' => 'Owner Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Owner Plot',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        $recommendation = CropRecommendation::create([
            'plot_id' => $plot->id,
            'crop_name' => 'Rice',
            'crop_type' => 'grain',
            'confidence_score' => 90,
            'reasoning' => 'Good soil',
            'estimated_yield_per_hectare' => 4.5,
            'estimated_gross_revenue_per_hectare' => 1500,
            'market_demand_level' => 'high',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($otherFarmer)
            ->patchJson("/api/v1/recommendations/{$recommendation->id}/status", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_farmer_can_update_recommendation_status(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);

        $farm = Farm::create(['user_id' => $owner->id, 'name' => 'Owner Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Owner Plot',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        $recommendation = CropRecommendation::create([
            'plot_id' => $plot->id,
            'crop_name' => 'Rice',
            'crop_type' => 'grain',
            'confidence_score' => 90,
            'reasoning' => 'Good soil',
            'estimated_yield_per_hectare' => 4.5,
            'estimated_gross_revenue_per_hectare' => 1500,
            'market_demand_level' => 'high',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/recommendations/{$recommendation->id}/status", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'accepted');
    }

    public function test_websocket_plot_channel_authorization(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => '12345',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $owner = User::factory()->create(['role' => 'farmer']);
        $otherUser = User::factory()->create(['role' => 'farmer']);

        $farm = Farm::create(['user_id' => $owner->id, 'name' => 'Owner Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Owner Plot',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'clay',
            'calculated_area' => 5.0,
        ]);

        // Unauthorized user attempt
        $responseUnauthorized = $this->actingAs($otherUser)
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-plot.{$plot->id}",
                'socket_id' => '1234.5678',
            ]);
        $responseUnauthorized->assertStatus(403);

        // Authorized owner attempt
        $responseAuthorized = $this->actingAs($owner)
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-plot.{$plot->id}",
                'socket_id' => '1234.5678',
            ]);
        $responseAuthorized->assertStatus(200)
            ->assertJsonStructure(['auth']);
    }
}
