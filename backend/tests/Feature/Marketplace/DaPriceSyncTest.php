<?php

use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropPriceSyncRun;
use App\Domain\Marketplace\Models\CropReferencePrice;
use Database\Seeders\CropPriceAliasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.da_prices.enabled', true);
    config()->set('services.da_prices.base_url', 'http://da.test');
    config()->set('services.da_prices.endpoints', [
        ['page' => 'rice', 'commodities' => [1]],
    ]);
    config()->set('services.da_prices.regions', ['130000000', '040000000']);
    config()->set('services.da_prices.timeout', 5);
    config()->set('services.da_prices.ai_fallback', false);
});

function fakeDaTable(string $commodity, string $price): void
{
    Http::fake(function ($request) use ($commodity, $price) {
        $url = (string) $request->url();

        if (str_contains($url, 'header_rice.php')) {
            return Http::response('<th>Commodity</th><th>Market A</th><th>Market B</th>');
        }

        if (str_contains($url, 'price_rice.php')) {
            return Http::response("<tr><td>{$commodity}</td><td>{$price}</td><td>{$price}</td></tr>");
        }

        return Http::response(null, 404);
    });
}

it('upserts regional rows plus a national average from DA endpoints', function () {
    fakeDaTable('Rice', '50.00');

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    expect(CropReferencePrice::where('crop_slug', 'rice')->where('region_code', '130000000')->first()->price_per_kg)
        ->toEqual('50.00');
    expect(CropReferencePrice::where('crop_slug', 'rice')->where('region_code', '040000000')->count())->toBe(1);

    $national = CropReferencePrice::where('crop_slug', 'rice')->whereNull('region_code')->first();
    expect($national)->not->toBeNull();
    expect($national->price_per_kg)->toEqual('50.00');

    expect(CropPriceSyncRun::latest()->first()->status)->toBe('success');
});

it('rolls DA variant grades up to base crops', function () {
    Http::fake(function ($request) {
        $url = (string) $request->url();

        if (str_contains($url, 'header_rice.php')) {
            return Http::response('<th>Commodity</th><th>Market A</th>');
        }

        if (str_contains($url, 'price_rice.php')) {
            return Http::response('<tr><td>Commercial Local Well Milled</td><td>50.00</td></tr>'
                .'<tr><td>Commercial Local Regular Milled</td><td>46.00</td></tr>');
        }

        return Http::response(null, 404);
    });

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    $rice = CropReferencePrice::where('crop_slug', 'rice')->where('region_code', '130000000')->first();
    expect($rice)->not->toBeNull();
    expect($rice->price_per_kg)->toEqual('48.00');
});

it('syncs the bulb and spice commodity group into base crops', function () {
    config()->set('services.da_prices.endpoints', [
        ['page' => 'veg', 'commodities' => [9]],
    ]);
    config()->set('services.da_prices.regions', ['130000000']);
    $this->seed(CropPriceAliasSeeder::class);

    Http::fake(function ($request) {
        $url = (string) $request->url();

        if (str_contains($url, 'price_veg.php')) {
            return Http::response('<tr><td>Red Onion</td><td>120.00</td></tr>'
                .'<tr><td>White Onion</td><td>100.00</td></tr>'
                .'<tr><td>Garlic(Native)</td><td>90.00</td></tr>');
        }

        return Http::response(null, 404);
    });

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    $onion = CropReferencePrice::where('crop_slug', 'onion')->whereNull('region_code')->first();
    expect($onion)->not->toBeNull();
    expect($onion->price_per_kg)->toEqual('110.00');

    $garlic = CropReferencePrice::where('crop_slug', 'garlic')->whereNull('region_code')->first();
    expect($garlic)->not->toBeNull();
    expect($garlic->price_per_kg)->toEqual('90.00');
});

it('retargets stale seed aliases on re-run', function () {
    CropPriceAlias::create(['alias_slug' => 'sitaw', 'crop_slug' => 'string-beans', 'source' => 'seed', 'verified' => true]);

    $this->seed(CropPriceAliasSeeder::class);

    expect(CropPriceAlias::where('alias_slug', 'sitaw')->first()->crop_slug)->toBe('sitao');
});

it('tolerates failing endpoints and keeps the rest', function () {
    Http::fake(function ($request) {
        $url = (string) $request->url();

        if (str_contains($url, 'header_rice.php')) {
            return Http::response('<th>Commodity</th><th>Market A</th>');
        }

        if (str_contains($url, 'price_rice.php') && ($request->data()['region'] ?? null) === '130000000') {
            return Http::response('<tr><td>Rice</td><td>50.00</td></tr>');
        }

        return Http::response(null, 500);
    });

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    expect(CropReferencePrice::where('crop_slug', 'rice')->where('region_code', '130000000')->count())->toBe(1);
    expect(CropPriceSyncRun::latest()->first()->status)->toBe('success');
});

it('records a failed run when all DA endpoints are unreachable', function () {
    Http::fake(['da.test/*' => Http::response(null, 500)]);

    $this->artisan('marketplace:sync-da-prices')->assertFailed();

    expect(CropPriceSyncRun::latest()->first()->status)->toBe('failed');
    expect(CropReferencePrice::count())->toBe(0);
});

it('falls back to AI extraction when HTML parsing finds nothing', function () {
    config()->set('services.da_prices.ai_fallback', true);
    config()->set('services.gemini.key', 'test-gemini-key');

    Http::fake([
        'da.test/*' => Http::response('<div>Rice premium 52 pesos per kilo</div>'),
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        ['crop_name' => 'Rice', 'tier' => 'retail', 'price_per_kg' => 52.00, 'market' => null],
                    ]),
                ]]],
            ]],
        ]),
    ]);

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    expect(CropReferencePrice::where('crop_slug', 'rice')->first()->price_per_kg)->toEqual('52.00');
});

it('stays off when disabled and keeps serving last-good data', function () {
    config()->set('services.da_prices.enabled', false);

    $this->artisan('marketplace:sync-da-prices')->assertFailed();

    expect(CropPriceSyncRun::latest()->first()->status)->toBe('disabled');
});

it('loads explicit sample rows only with --sample', function () {
    $this->artisan('marketplace:sync-da-prices --sample')->assertSuccessful();

    expect(CropReferencePrice::where('source', 'manual')->count())->toBeGreaterThan(0);
});

it('retries a flaky DA endpoint until it answers', function () {
    config()->set('services.da_prices.regions', ['130000000']);

    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $url = (string) $request->url();

        if (str_contains($url, 'header_rice.php')) {
            return Http::response('<th>Commodity</th><th>Market A</th>');
        }

        if (str_contains($url, 'price_rice.php')) {
            $attempts++;

            if ($attempts < 3) {
                return Http::response(null, 500);
            }

            return Http::response('<tr><td>Rice</td><td>50.00</td></tr>');
        }

        return Http::response(null, 404);
    });

    $this->artisan('marketplace:sync-da-prices')->assertSuccessful();

    expect($attempts)->toBe(3);
    expect(CropReferencePrice::where('crop_slug', 'rice')->where('region_code', '130000000')->count())->toBe(1);
    expect(CropPriceSyncRun::latest()->first()->status)->toBe('success');
});

it('visits a dead DA endpoint ten times before giving up', function () {
    config()->set('services.da_prices.regions', ['130000000']);
    Http::fake(['da.test/*' => Http::response(null, 500)]);

    $this->artisan('marketplace:sync-da-prices')->assertFailed();

    Http::assertSentCount(10);
    expect(CropPriceSyncRun::latest()->first()->status)->toBe('failed');
});
