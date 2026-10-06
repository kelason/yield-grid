<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crop_recommendations', function (Blueprint $table): void {
            $table->string('produce_type', 20)->nullable();
            $table->string('subtype', 30)->nullable();
            $table->index('produce_type');
            $table->index('subtype');
        });
    }

    public function down(): void
    {
        Schema::table('crop_recommendations', function (Blueprint $table): void {
            $table->dropIndex(['produce_type']);
            $table->dropIndex(['subtype']);
            $table->dropColumn(['produce_type', 'subtype']);
        });
    }
};
