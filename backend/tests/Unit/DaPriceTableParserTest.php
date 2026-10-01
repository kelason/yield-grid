<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Infrastructure\Services\DaPriceTableParser;
use Tests\TestCase;

uses(TestCase::class);

it('averages market columns into one regional row', function () {
    $rows = DaPriceTableParser::parse(
        '<th>Commodity</th><th>Commonwealth</th><th>Marikina</th>',
        '<tr><td>Rice Special</td><td>52.00</td><td>54.00</td></tr>',
        '130000000'
    );

    expect($rows)->toHaveCount(1);
    expect($rows[0]['crop_slug'])->toBe('rice-special')
        ->and($rows[0]['tier'])->toBe(PriceTier::RETAIL->value)
        ->and($rows[0]['price_per_kg'])->toEqual(53.00)
        ->and($rows[0]['region_code'])->toBe('130000000')
        ->and($rows[0]['source'])->toBe(PriceSource::DA_BANTAY_PRESYO->value)
        ->and($rows[0]['observed_at'])->toBe(now()->toDateString());
});

it('uses the latest date column when headers are dates', function () {
    $rows = DaPriceTableParser::parse(
        '<th>Commodity</th><th>2026-09-30</th><th>2026-10-01</th>',
        '<tr><td>Rice</td><td>51.00</td><td>52.00</td></tr>',
        '130000000'
    );

    expect($rows)->toHaveCount(1);
    expect($rows[0]['price_per_kg'])->toEqual(52.00)
        ->and($rows[0]['observed_at'])->toBe('2026-10-01');
});

it('excludes metadata columns from the average', function () {
    $rows = DaPriceTableParser::parse(
        '<th>Commodity</th><th>Price</th><th>% Change</th>',
        '<tr><td>Rice</td><td>52.00</td><td>1.50</td></tr>',
        '130000000'
    );

    expect($rows)->toHaveCount(1);
    expect($rows[0]['price_per_kg'])->toEqual(52.00);
});

it('skips summary rows and non-numeric cells', function () {
    $rows = DaPriceTableParser::parse(
        '<th>Commodity</th><th>Price</th>',
        '<tr><td>TOTAL</td><td>999.00</td></tr>'
        .'<tr><td>Rice</td><td>N/A</td></tr>'
        .'<tr><td>Corn</td><td>32.50</td></tr>',
        '130000000'
    );

    expect($rows)->toHaveCount(1);
    expect($rows[0]['crop_slug'])->toBe('corn');
});

it('returns nothing for empty or garbage markup', function () {
    expect(DaPriceTableParser::parse('', '', '130000000'))->toBe([]);
    expect(DaPriceTableParser::parse(null, null, '130000000'))->toBe([]);
    expect(DaPriceTableParser::parse('<div>no table here</div>', '<p>nope</p>', '130000000'))->toBe([]);
});
