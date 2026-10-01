<?php

use App\Infrastructure\Services\ReverseGeocodeService;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('returns the cached resolution without issuing HTTP requests', function () {
    Cache::put('geo_coord_15.401_120.699', [
        'city' => 'La Paz',
        'state' => 'Tarlac',
        'country' => 'Philippines',
    ], 60);
    Http::fake();

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(15.401, 120.699);

    expect($geo)->toBe(['city' => 'La Paz', 'state' => 'Tarlac', 'country' => 'Philippines']);
    Http::assertNothingSent();
});

it('normalizes a Photon response and caches the resolution', function () {
    Http::fake([
        'photon.komoot.io/reverse*' => Http::response([
            'features' => [
                ['properties' => ['town' => 'La Paz', 'county' => 'Tarlac', 'country' => 'Philippines']],
            ],
        ], 200),
    ]);

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(15.401, 120.699);

    expect($geo)->toBe(['city' => 'La Paz', 'state' => 'Tarlac', 'country' => 'Philippines']);
    expect(Cache::get('geo_coord_15.401_120.699'))->toBe($geo);
});

it('falls back to BigDataCloud when Photon resolves nothing', function () {
    Http::fake([
        'photon.komoot.io/reverse*' => Http::response(['features' => []], 200),
        'api.bigdatacloud.net/data/reverse-geocode-client*' => Http::response([
            'city' => 'Cabanatuan City',
            'localityInfo' => [
                'administrative' => [
                    ['adminLevel' => 4, 'name' => 'Nueva Ecija', 'description' => 'province'],
                ],
            ],
            'countryName' => 'Philippines',
        ], 200),
    ]);

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(15.474, 121.034);

    expect($geo)->toBe(['city' => 'Cabanatuan City', 'state' => 'Nueva Ecija', 'country' => 'Philippines']);
    Http::assertSentCount(2);
});

it('strips parenthetical suffixes from the BigDataCloud subdivision fallback', function () {
    Http::fake([
        'photon.komoot.io/reverse*' => Http::response([], 500),
        'api.bigdatacloud.net/data/reverse-geocode-client*' => Http::response([
            'locality' => 'Bantug',
            'principalSubdivision' => 'Nueva Ecija (Province)',
            'countryName' => 'Philippines',
        ], 200),
    ]);

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(15.49, 120.968);

    expect($geo)->toBe(['city' => 'Bantug', 'state' => 'Nueva Ecija', 'country' => 'Philippines']);
});

it('returns null and caches nothing when both providers fail', function () {
    Http::fake([
        'photon.komoot.io/reverse*' => Http::response([], 500),
        'api.bigdatacloud.net/data/reverse-geocode-client*' => Http::response([], 500),
    ]);

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(0.0, 0.0);

    expect($geo)->toBeNull();
    expect(Cache::missing('geo_coord_0_0'))->toBeTrue();
});

it('resolves a plot location from the farm address when coordinates yield nothing', function () {
    Http::fake();
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create([
        'user_id' => $farmer->id,
        'name' => 'Test Farm',
        'city' => 'La Paz',
        'state' => 'Tarlac',
        'country' => 'Philippines',
    ]);
    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10,
    ]);

    $location = app(ReverseGeocodeService::class)->resolvePlotLocation($plot);

    expect($location)->toBe(['city' => 'La Paz', 'state' => 'Tarlac', 'country' => 'Philippines']);
});

it('resolves a plot location to country defaults when nothing is known', function () {
    Http::fake();
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10,
    ]);

    $location = app(ReverseGeocodeService::class)->resolvePlotLocation($plot);

    expect($location)->toBe(['city' => 'Local Region', 'state' => '', 'country' => 'Philippines']);
});
