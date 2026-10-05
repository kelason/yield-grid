<?php

use App\Console\Commands\RefreshDemoDatabaseCommand;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('builds timestamped backup filenames', function () {
    $command = app(RefreshDemoDatabaseCommand::class);

    expect($command->backupFilename(Carbon::parse('2026-10-05 00:00:00')))
        ->toBe('yieldgrid-20261005-000000.dump');
});

it('prunes only backups older than the retention window', function () {
    $dir = sys_get_temp_dir().'/prune-test-'.uniqid();
    mkdir($dir);
    $old = $dir.'/yieldgrid-20200101-000000.dump';
    $fresh = $dir.'/yieldgrid-20990101-000000.dump';
    $notes = $dir.'/README.txt';
    touch($old, time() - 10 * 86400);
    touch($fresh);
    touch($notes);

    $deleted = app(RefreshDemoDatabaseCommand::class)->pruneBackups($dir, 7);

    expect($deleted)->toBe(1)
        ->and(file_exists($old))->toBeFalse()
        ->and(file_exists($fresh))->toBeTrue()
        ->and(file_exists($notes))->toBeTrue();

    array_map(unlink(...), glob($dir.'/*'));
    rmdir($dir);
});

it('treats a missing backup directory as nothing to prune', function () {
    $command = app(RefreshDemoDatabaseCommand::class);

    expect($command->pruneBackups(sys_get_temp_dir().'/missing-'.uniqid(), 7))->toBe(0);
});
