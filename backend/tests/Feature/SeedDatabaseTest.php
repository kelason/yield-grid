<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropPriceSyncRun;
use App\Domain\Marketplace\Models\CropReferencePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('seeds a freshly migrated database', function () {
    $this->seed();

    expect(CropPriceAlias::count())->toBeGreaterThan(0);
});

it('restores synced DA prices from the snapshot', function () {
    $this->seed();

    expect(CropReferencePrice::where('source', PriceSource::DA_BANTAY_PRESYO)->count())->toBeGreaterThan(0);
    expect(CropPriceSyncRun::count())->toBeGreaterThan(0);
});

it('runs against the isolated testing database, never the dev database', function () {
    // RefreshDatabase migrates fresh: pointed at dev it would wipe real data.
    // Parallel workers use suffixed databases (yieldgrid_testing_test_N).
    expect(DB::getDatabaseName())->toMatch('/^yieldgrid_testing(_test_\d+)?$/');
});
