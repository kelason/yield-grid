<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable()->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason', 500)->nullable();
        });

        Schema::table('forum_replies', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable()->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_replies', function (Blueprint $table): void {
            $table->dropForeign(['hidden_by']);
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'hidden_by', 'hidden_reason']);
        });

        Schema::table('forum_threads', function (Blueprint $table): void {
            $table->dropForeign(['hidden_by']);
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'hidden_by', 'hidden_reason']);
        });
    }
};
