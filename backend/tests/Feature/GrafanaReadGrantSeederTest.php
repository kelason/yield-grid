<?php

use Database\Seeders\GrafanaReadGrantSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('skips the grafana grant restore on non-pgsql connections', function () {
    expect(DB::getDriverName())->toBe('sqlite');

    (new GrafanaReadGrantSeeder)->run();
});
