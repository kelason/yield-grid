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
        Schema::create('flood_hazard_zones', function (Blueprint $table) {
            $table->id();
            $table->string('hazard_class');
            $table->integer('return_period_years')->default(5);
            $table->geometry('polygon', subtype: 'multipolygon', srid: 4326)->nullable();
            $table->string('source')->default('project-noah');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX flood_hazard_zones_polygon_gist ON flood_hazard_zones USING GIST (polygon)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flood_hazard_zones');
    }
};
