<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('rsbsa_number', 30)->nullable();
            $table->string('rsbsa_status', 20)->default('not_registered');
            $table->timestamps();
        });

        Schema::create('insurance_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plot_id')->nullable()->constrained('plots')->onDelete('set null');
            $table->string('program', 10);
            $table->string('season', 10);
            $table->unsignedSmallInteger('season_year');
            $table->string('status', 20)->default('draft');
            $table->string('cic_number', 30)->nullable();
            $table->decimal('coverage_amount_php', 12, 2)->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('insurance_enrollments')->onDelete('cascade');
            $table->date('loss_date');
            $table->string('cause', 20);
            $table->string('description', 1000)->nullable();
            $table->string('status', 25)->default('draft');
            $table->timestamp('notice_of_loss_filed_at')->nullable();
            $table->decimal('paid_amount_php', 12, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'status']);
        });

        Schema::create('planting_windows', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 12);
            $table->string('program', 10);
            $table->string('season', 10);
            $table->unsignedTinyInteger('window_start_month');
            $table->unsignedTinyInteger('window_start_day');
            $table->unsignedTinyInteger('window_end_month');
            $table->unsignedTinyInteger('window_end_day');
            $table->string('source', 120);
            $table->timestamps();

            $table->unique(['region_code', 'program', 'season']);
        });

        Schema::create('insurance_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('region_code', 12)->nullable()->unique();
            $table->string('city', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('source_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 30);
            $table->string('reference_key', 60);
            $table->jsonb('meta')->nullable();
            $table->foreignId('enrollment_id')->nullable()->constrained('insurance_enrollments')->onDelete('cascade');
            $table->foreignId('claim_id')->nullable()->constrained('insurance_claims')->onDelete('cascade');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'reference_key']);
            $table->index(['user_id', 'type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_reminder_logs');
        Schema::dropIfExists('insurance_offices');
        Schema::dropIfExists('planting_windows');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_enrollments');
        Schema::dropIfExists('insurance_profiles');
    }
};
