<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table): void {
            $table->string('verification_status', 20)->default('pending')->index();
            $table->string('verification_method', 20)->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
        });

        Schema::table('plots', function (Blueprint $table): void {
            $table->string('verification_status', 20)->default('pending')->index();
            $table->string('verification_method', 20)->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
        });

        Schema::table('harvest_listings', function (Blueprint $table): void {
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->foreignId('plot_id')->nullable()->constrained('plots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('harvest_listings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('farm_id');
            $table->dropConstrainedForeignId('plot_id');
        });

        Schema::table('plots', function (Blueprint $table): void {
            $table->dropIndex(['verification_status']);
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_status', 'verification_method', 'verification_note', 'verified_at']);
        });

        Schema::table('farms', function (Blueprint $table): void {
            $table->dropIndex(['verification_status']);
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_status', 'verification_method', 'verification_note', 'verified_at']);
        });
    }
};
