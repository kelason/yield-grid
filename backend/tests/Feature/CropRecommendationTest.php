<?php

namespace Tests\Feature;

use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use App\Domain\CropRecommendation\Services\AgroMonitoringService;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
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

        Queue::assertPushed(AnalyzePlotJob::class, function (AnalyzePlotJob $job) use ($plot) {
            return $job->plotId === $plot->id && $job->preferences === null;
        });
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

    public function test_it_dispatches_analysis_with_preferences(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot P',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0,
        ]);

        $prefs = [
            'produce_types' => ['fruit'],
            'subtypes' => ['citrus'],
            'irrigation' => 'limited',
            'goal' => 'quick_cash',
        ];

        $this->actingAs($user)
            ->postJson("/api/v1/plots/{$plot->id}/analyze", $prefs)
            ->assertStatus(202);

        Queue::assertPushed(AnalyzePlotJob::class, function (AnalyzePlotJob $job) use ($plot, $prefs) {
            return $job->plotId === $plot->id && $job->preferences === $prefs;
        });
    }

    public function test_it_rejects_invalid_preferences(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot Q',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/plots/{$plot->id}/analyze", [
                'subtypes' => ['not-a-crop'],
                'irrigation' => 'firehose',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subtypes.0', 'irrigation']);

        Queue::assertNothingPushed();
    }

    public function test_it_accepts_explicit_null_preferences(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot N',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/plots/{$plot->id}/analyze", [
                'produce_types' => null,
                'subtypes' => null,
                'irrigation' => null,
                'goal' => null,
            ])
            ->assertStatus(202);

        Queue::assertPushed(AnalyzePlotJob::class, function (AnalyzePlotJob $job) use ($plot) {
            return $job->plotId === $plot->id && $job->preferences === null;
        });
    }

    public function test_analysis_persists_taxonomy_tags(): void
    {
        config(['services.gemini.key' => '', 'services.agromonitoring.key' => '']);

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot T',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0,
        ]);

        AnalyzePlotJob::dispatchSync($plot->id, [
            'produce_types' => [],
            'subtypes' => ['citrus'],
            'irrigation' => null,
            'goal' => null,
        ]);

        $recs = CropRecommendation::where('plot_id', $plot->id)->orderBy('id')->get();

        $this->assertCount(10, $recs);
        $this->assertTrue($recs->every(fn (CropRecommendation $rec) => $rec->produce_type === 'fruit'));
        $this->assertEquals(
            ['citrus', 'citrus', 'citrus'],
            $recs->take(3)->map(fn (CropRecommendation $rec) => $rec->subtype)->values()->all()
        );

        $this->actingAs($user)
            ->getJson("/api/v1/plots/{$plot->id}/recommendations")
            ->assertOk()
            ->assertJsonPath('data.0.produce_type', 'fruit')
            ->assertJsonPath('data.0.subtype', 'citrus');
    }

    public function test_analysis_lifts_demanded_crops(): void
    {
        config(['services.gemini.key' => '', 'services.agromonitoring.key' => '']);

        $user = User::factory()->create(['role' => 'farmer']);
        $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
        $plot = Plot::create([
            'farm_id' => $farm->id,
            'name' => 'Plot M',
            'polygon' => '{"type": "Polygon", "coordinates": []}',
            'soil_type' => 'loamy',
            'calculated_area' => 2.0,
        ]);

        AnalyzePlotJob::dispatchSync($plot->id);

        $this->assertNotContains(
            'Calamansi',
            CropRecommendation::where('plot_id', $plot->id)->pluck('crop_name')->all()
        );

        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = UserAddress::create([
            'user_id' => $buyer->id,
            'label' => 'Home',
            'region_code' => '03',
            'city_municipality_code' => '034901',
            'barangay_code' => '034901001',
            'is_default' => true,
        ]);
        CropDemand::create([
            'buyer_id' => $buyer->id,
            'address_id' => $address->id,
            'title' => 'Calamansi wanted',
            'crop_name' => 'Calamansi',
            'quantity_kg' => 500,
            'remaining_quantity_kg' => 500,
            'target_price_per_kg' => 45,
            'total_budget' => 22500,
            'currency' => 'PHP',
            'needed_by_date' => now()->addDays(30)->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'status' => DemandStatus::OPEN,
        ]);

        AnalyzePlotJob::dispatchSync($plot->id);

        $this->assertContains(
            'Calamansi',
            CropRecommendation::where('plot_id', $plot->id)->pluck('crop_name')->all()
        );
    }

    public function test_taxonomy_endpoint_returns_tree_and_options(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-taxonomy')
            ->assertOk()
            ->assertJsonPath('data.types.vegetable.label', 'Vegetables')
            ->assertJsonPath('data.types.fruit.subtypes.citrus.label', 'Citrus')
            ->assertJsonCount(9, 'data.types.vegetable.subtypes.fruiting.crops')
            ->assertJsonFragment([
                'slug' => 'tomato',
                'name' => 'Tomatoes (Kamatis)',
                'type' => 'vegetable',
                'subtype' => 'fruiting',
            ])
            ->assertJsonStructure([
                'data' => [
                    'irrigation_levels' => [['value', 'label']],
                    'goals' => [['value', 'label']],
                ],
            ]);
    }
}
