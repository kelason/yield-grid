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
        Schema::table('plots', function (Blueprint $table) {
            $table->string('flood_risk_level')->nullable();
            $table->boolean('flood_within_coverage')->nullable();
            $table->timestamp('flood_risk_assessed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->dropColumn(['flood_risk_level', 'flood_within_coverage', 'flood_risk_assessed_at']);
        });
    }
};
