<?php

use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use Domain\Users\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function contentReportMigrationInstance(): Migration
{
    $migration = require database_path('migrations/2026_10_08_000004_generalize_content_reports.php');

    expect($migration)->toBeInstanceOf(Migration::class);

    return $migration;
}

it('renames forum_reports to content_reports preserving every legacy row', function () {
    $migration = contentReportMigrationInstance();
    $migration->down();

    expect(Schema::hasTable('forum_reports'))->toBeTrue()
        ->and(Schema::hasTable('content_reports'))->toBeFalse();

    $reporter = User::factory()->create();
    $secondReporter = User::factory()->create();

    $stamps = ['created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-02 11:00:00'];

    DB::table('forum_reports')->insert([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 42,
        'reason' => ContentReportReason::SPAM->value,
        'description' => 'Legacy description.',
        ...$stamps,
    ]);
    DB::table('forum_reports')->insert([
        'user_id' => $secondReporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 42,
        'reason' => ContentReportReason::OTHER->value,
        'description' => 'Second reporter, same target.',
        ...$stamps,
    ]);
    DB::table('forum_reports')->insert([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::REPLY->value,
        'reportable_id' => 999999,
        'reason' => ContentReportReason::HARASSMENT->value,
        'description' => null,
        ...$stamps,
    ]);
    DB::table('forum_reports')->insert([
        'user_id' => $reporter->id,
        'reportable_type' => 'user',
        'reportable_id' => 7,
        'reason' => 'bad-vibes',
        'description' => 'Never-valid legacy row.',
        ...$stamps,
    ]);

    $before = DB::table('forum_reports')->orderBy('id')->get();

    expect($before)->toHaveCount(4);

    $migration->up();

    expect(Schema::hasTable('content_reports'))->toBeTrue()
        ->and(Schema::hasTable('forum_reports'))->toBeFalse();

    $after = DB::table('content_reports')->orderBy('id')->get();

    expect($after)->toHaveCount(4);

    foreach ($before as $index => $row) {
        $migrated = $after[$index];

        expect($migrated->id)->toBe($row->id)
            ->and($migrated->user_id)->toBe($row->user_id)
            ->and($migrated->reportable_type)->toBe($row->reportable_type)
            ->and($migrated->reportable_id)->toBe($row->reportable_id)
            ->and($migrated->reason)->toBe($row->reason)
            ->and($migrated->description)->toBe($row->description)
            ->and((string) $migrated->created_at)->toBe((string) $row->created_at)
            ->and((string) $migrated->updated_at)->toBe((string) $row->updated_at)
            ->and($migrated->status)->toBe(ContentReportStatus::OPEN->value)
            ->and($migrated->version)->toBe(1)
            ->and($migrated->target_snapshot)->toBeNull();
    }
});

it('keeps the lifetime reporter-target uniqueness after the rename', function () {
    $reporter = User::factory()->create();

    ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 42,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    try {
        DB::transaction(function () use ($reporter): void {
            ContentReport::create([
                'user_id' => $reporter->id,
                'reportable_type' => ReportTargetType::THREAD->value,
                'reportable_id' => 42,
                'reason' => ContentReportReason::HARASSMENT->value,
                'description' => null,
            ]);
        });
        $this->fail('Expected the duplicate report to violate the unique key.');
    } catch (QueryException $e) {
        expect((string) $e->getCode())->toBe('23505');
    }

    expect(ContentReport::count())->toBe(1);
});

it('assigns new ids beyond the migrated maximum', function () {
    $reporter = User::factory()->create();

    DB::table('content_reports')->insert([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 42,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $max = (int) DB::table('content_reports')->max('id');

    $report = ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 43,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    expect($report->id)->toBeGreaterThan($max);
});

it('preserves report evidence when the reporter is deleted', function () {
    $reporter = User::factory()->buyer()->create();

    $report = ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 42,
        'reason' => ContentReportReason::SPAM->value,
        'description' => 'Evidence stays.',
    ]);

    $reporter->delete();

    $row = DB::table('content_reports')->where('id', $report->id)->firstOrFail();

    expect($row->user_id)->toBeNull()
        ->and($row->reason)->toBe(ContentReportReason::SPAM->value)
        ->and($row->description)->toBe('Evidence stays.');
});

it('indexes the report queue for status and target lookups', function () {
    $indexes = DB::select(
        "SELECT indexname FROM pg_indexes WHERE tablename = 'content_reports'"
    );

    $names = array_column(array_map(fn ($row) => (array) $row, $indexes), 'indexname');

    expect($names)->toContain('content_reports_status_created_at_id_index');
});
