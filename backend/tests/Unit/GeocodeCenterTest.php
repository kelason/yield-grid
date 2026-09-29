<?php

use App\Infrastructure\Services\PsgcService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
});

it('anchors barangay search to the geocoded city so same-named places cannot win', function () {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::sequence()
            // Structured city query: Cabanatuan City, Nueva Ecija.
            ->push([['lat' => '15.4852', 'lon' => '120.9668']])
            // Bounded barangay query inside the Cabanatuan viewbox.
            ->push([['lat' => '15.4900', 'lon' => '120.9700']]),
    ]);

    $center = app(PsgcService::class)->geocodeCenter('Bantug Norte', 'Cabanatuan City', 'Nueva Ecija');

    expect($center)->toBe(['lat' => 15.49, 'lng' => 120.97]);

    Http::assertSent(function ($request) {
        $params = $request->data();

        return ($params['city'] ?? null) === 'Cabanatuan City'
            && ($params['state'] ?? null) === 'Nueva Ecija'
            && ($params['country'] ?? null) === 'Philippines';
    });

    Http::assertSent(function ($request) {
        $params = $request->data();

        return isset($params['q'], $params['viewbox']) && (int) ($params['bounded'] ?? 0) === 1;
    });
});

it('retries the city anchor with the " City" suffix stripped', function () {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::sequence()
            // Verbatim "Cabanatuan City" finds nothing in structured search.
            ->push([])
            // Stripped "Cabanatuan" resolves.
            ->push([['lat' => '15.4905', 'lon' => '120.9684']])
            // Bounded barangay query finds nothing mapped.
            ->push([]),
    ]);

    $center = app(PsgcService::class)->geocodeCenter('Bantug Norte', 'Cabanatuan City', 'Nueva Ecija');

    expect($center)->toBe(['lat' => 15.4905, 'lng' => 120.9684]);

    Http::assertSent(function ($request) {
        return ($request->data()['city'] ?? null) === 'Cabanatuan';
    });
});

it('falls back to the city center when the barangay is not mapped', function () {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::sequence()
            ->push([['lat' => '15.4852', 'lon' => '120.9668']])
            ->push([]),
    ]);

    $center = app(PsgcService::class)->geocodeCenter('Bantug Norte', 'Cabanatuan City', 'Nueva Ecija');

    expect($center)->toBe(['lat' => 15.4852, 'lng' => 120.9668]);
});

it('returns null when geocoding fails entirely', function () {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 500),
    ]);

    expect(app(PsgcService::class)->geocodeCenter('Nowhere', 'Nowhere City', 'Nowhere'))->toBeNull();
});
