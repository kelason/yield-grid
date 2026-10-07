<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('scheduler_heartbeats');
    Schema::create('scheduler_heartbeats', function (Blueprint $table) {
        $table->id();
        $table->timestamp('beat_at');
    });
});

it('registers a five-minute scheduler heartbeat', function () {
    $events = collect(app(Schedule::class)->events());

    expect($events->contains(fn ($event) => $event->expression === '*/5 * * * *'))->toBeTrue();
});

it('upserts the heartbeat row', function () {
    DB::table('scheduler_heartbeats')->updateOrInsert(['id' => 1], ['beat_at' => now()]);

    expect(DB::table('scheduler_heartbeats')->find(1)->beat_at)->not->toBeNull();
});
