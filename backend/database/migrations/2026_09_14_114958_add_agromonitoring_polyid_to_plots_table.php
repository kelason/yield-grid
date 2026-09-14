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
            $table->string('agromonitoring_polyid')->nullable()->after('calculated_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->dropColumn('agromonitoring_polyid');
        });
    }
};
