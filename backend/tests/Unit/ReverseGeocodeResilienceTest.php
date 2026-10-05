<?php

use App\Infrastructure\Services\ReverseGeocodeService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\AbstractLogger;
use Tests\TestCase;

uses(TestCase::class);

it('returns resolved data when the cache store fails', function () {
    Http::fake([
        'photon.komoot.io/reverse*' => Http::response([
            'features' => [
                ['properties' => ['town' => 'La Paz', 'county' => 'Tarlac', 'country' => 'Philippines']],
            ],
        ], 200),
    ]);
    Cache::swap(new class
    {
        public function get(string $key, mixed $default = null): mixed
        {
            throw new RuntimeException('Cache store unreachable (simulated unwritable storage).');
        }

        public function put(string $key, mixed $value, mixed $ttl = null): bool
        {
            throw new RuntimeException('Cache store unreachable (simulated unwritable storage).');
        }
    });

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(15.401, 120.699);

    expect($geo)->toBe(['city' => 'La Paz', 'state' => 'Tarlac', 'country' => 'Philippines']);
});

it('returns null when providers fail and logging also fails', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 7: Failed to connect (simulated).');
    });
    Log::swap(new class extends AbstractLogger
    {
        public function log($level, string|Stringable $message, array $context = []): void
        {
            throw new UnexpectedValueException('The stream or file "laravel.log" could not be opened: Permission denied.');
        }
    });

    $geo = app(ReverseGeocodeService::class)->reverseGeocode(0.0, 0.0);

    expect($geo)->toBeNull();
});
