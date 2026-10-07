<?php

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2026_10_07_103133_create_scheduler_heartbeats_table.php',
        '--force' => true,
    ]);
});

function heartbeatEvents(): Collection
{
    return collect(app(Schedule::class)->events())
        ->filter(fn ($event) => $event->expression === '*/5 * * * *');
}

it('registers exactly one five-minute scheduler heartbeat', function () {
    expect(heartbeatEvents())->toHaveCount(1);
});

it('writes a fresh heartbeat when the scheduled closure runs', function () {
    heartbeatEvents()->first()->run(app());

    $beatAt = DB::table('scheduler_heartbeats')->find(1)->beat_at;

    expect($beatAt)->not->toBeNull();
    expect(Carbon::parse($beatAt)->greaterThan(now()->subMinute()))->toBeTrue();
});
