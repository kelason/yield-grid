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
        Schema::table('forward_contracts', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable()->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason', 500)->nullable();
            $table->foreignId('moderation_root_id')->nullable()->constrained('forward_contracts')->restrictOnDelete();
        });

        Schema::table('harvest_listings', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable()->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason', 500)->nullable();
            $table->foreignId('moderation_root_id')->nullable()->constrained('harvest_listings')->restrictOnDelete();
        });

        Schema::table('crop_demands', function (Blueprint $table): void {
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
        Schema::table('crop_demands', function (Blueprint $table): void {
            $table->dropForeign(['hidden_by']);
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'hidden_by', 'hidden_reason']);
        });

        Schema::table('harvest_listings', function (Blueprint $table): void {
            $table->dropForeign(['moderation_root_id']);
            $table->dropForeign(['hidden_by']);
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'hidden_by', 'hidden_reason', 'moderation_root_id']);
        });

        Schema::table('forward_contracts', function (Blueprint $table): void {
            $table->dropForeign(['moderation_root_id']);
            $table->dropForeign(['hidden_by']);
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'hidden_by', 'hidden_reason', 'moderation_root_id']);
        });
    }
};
