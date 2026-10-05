<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('marketplace:expire-contracts')->dailyAt('00:00');
Schedule::command('marketplace:expire-demands')->dailyAt('00:05');
Schedule::command('marketplace:sync-da-prices')->dailyAt('01:00');
Schedule::command('credit-scoring:recalculate-all')->dailyAt('02:00');
Schedule::command('insurance:send-reminders')->dailyAt('03:00');
