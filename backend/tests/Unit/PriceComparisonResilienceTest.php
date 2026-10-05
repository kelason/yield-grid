<?php

use App\Domain\Marketplace\Actions\GetPriceComparisonAction;
use App\Domain\Marketplace\Actions\NormalizeCropNameAction;
use App\Domain\Marketplace\DTOs\PriceGuideData;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

uses(TestCase::class);

function throwingBusAndEmptyCatalog(): CropReferencePriceRepositoryInterface
{
    Bus::shouldReceive('dispatch')->andThrow(new Exception('Queue backend unreachable (simulated).'));

    $prices = Mockery::mock(CropReferencePriceRepositoryInterface::class);
    $prices->shouldReceive('cropCatalog')->andReturn([]);
    $prices->shouldReceive('findAlias')->andReturn(null);

    return $prices;
}

it('returns null from crop normalization when the alias-learning dispatch fails', function () {
    $normalizer = new NormalizeCropNameAction(throwingBusAndEmptyCatalog());

    expect($normalizer->execute('ZzxqyuvwCrop'))->toBeNull();
});

it('returns unknown-crop data when the estimate dispatch fails', function () {
    $prices = throwingBusAndEmptyCatalog();
    $action = new GetPriceComparisonAction(new NormalizeCropNameAction($prices), $prices);

    $result = $action->execute('ZzxqyuvwCrop', null, ['yieldgrid']);

    expect($result->available)->toBeFalse();
    expect($result->reason)->toBe(PriceGuideData::REASON_UNKNOWN_CROP);
});
