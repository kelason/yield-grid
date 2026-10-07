<?php

use Database\Seeders\GrafanaReadGrantSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('skips the grafana grant restore on non-pgsql connections', function () {
    config(['database.default' => 'sqlite']);

    expect(DB::getDriverName())->toBe('sqlite');

    DB::enableQueryLog();

    (new GrafanaReadGrantSeeder)->run();

    expect(DB::getQueryLog())->toBeEmpty();
});
