<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->nullable();
            $table->string('region_code', 10);
            $table->string('province_code', 10)->nullable();
            $table->string('city_municipality_code', 10);
            $table->string('barangay_code', 10);
            $table->string('street')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
            $table->index('barangay_code');
        });

        // Geography (not geometry) so ST_Distance/ST_DWithin return meters for nearest-first sorting.
        // Plain typmod DDL: the AddGeographyColumn() helper was removed in PostGIS 3.
        DB::statement('ALTER TABLE user_addresses ADD COLUMN location geography(Point, 4326)');
        DB::statement('CREATE INDEX user_addresses_location_gist ON user_addresses USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
