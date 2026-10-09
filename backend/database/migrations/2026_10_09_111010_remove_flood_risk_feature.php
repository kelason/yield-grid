<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the retired flood-risk feature schema.
     *
     * Defensive: environments that never ran the flood migrations
     * (fresh installs after feature removal) migrate cleanly.
     */
    public function up(): void
    {
        Schema::dropIfExists('flood_hazard_zones');

        foreach (['flood_risk_level', 'flood_within_coverage', 'flood_risk_assessed_at'] as $column) {
            if (Schema::hasColumn('plots', $column)) {
                Schema::table('plots', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('flood_hazard_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('hazard_class');
            $table->integer('return_period_years')->default(5);
            $table->geometry('polygon', subtype: 'multipolygon', srid: 4326)->nullable();
            $table->string('source')->default('project-noah');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX flood_hazard_zones_polygon_gist ON flood_hazard_zones USING GIST (polygon)');

        Schema::table('plots', function (Blueprint $table): void {
            $table->string('flood_risk_level')->nullable();
            $table->boolean('flood_within_coverage')->nullable();
            $table->timestamp('flood_risk_assessed_at')->nullable();
        });
    }
};
