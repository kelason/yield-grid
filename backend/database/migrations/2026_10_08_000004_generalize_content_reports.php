<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forum_reports', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::rename('forum_reports', 'content_reports');

        DB::statement('ALTER TABLE content_reports ALTER COLUMN user_id DROP NOT NULL');

        Schema::table('content_reports', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('status', 16)->default('open');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('resolution_note', 500)->nullable();
            $table->string('outcome', 16)->nullable();
            $table->json('target_snapshot')->nullable();

            $table->index(['status', 'created_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->guardRollback();

        Schema::table('content_reports', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'created_at', 'id']);
            $table->dropColumn([
                'status',
                'version',
                'reviewed_by',
                'reviewed_at',
                'resolution_note',
                'outcome',
                'target_snapshot',
            ]);
            $table->dropForeign(['user_id']);
        });

        Schema::rename('content_reports', 'forum_reports');

        DB::statement('ALTER TABLE forum_reports ALTER COLUMN user_id SET NOT NULL');

        Schema::table('forum_reports', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function guardRollback(): void
    {
        $orphaned = DB::table('content_reports')->whereNull('user_id')->count();

        if ($orphaned > 0) {
            throw new RuntimeException(
                "Cannot roll back: {$orphaned} content report(s) have no reporter. Use a forward migration instead."
            );
        }

        $decided = DB::table('content_reports')
            ->where('status', '!=', 'open')
            ->orWhere('version', '!=', 1)
            ->orWhereNotNull('reviewed_by')
            ->count();

        if ($decided > 0) {
            throw new RuntimeException(
                "Cannot roll back: {$decided} content report(s) have review state. Use a forward migration instead."
            );
        }
    }
};
