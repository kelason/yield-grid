<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_score_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('overall_score');
            $table->string('tier', 20);
            $table->jsonb('dimension_scores');
            $table->jsonb('raw_metrics');
            $table->string('report_status', 20)->default('none');
            $table->string('report_path')->nullable();
            $table->string('report_token', 64)->nullable()->unique();
            $table->timestamp('report_generated_at')->nullable();
            $table->timestamp('report_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_score_snapshots');
    }
};
