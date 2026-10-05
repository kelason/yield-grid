<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_enrollments', function (Blueprint $table) {
            $table->string('pack_status', 20)->default('none');
            $table->string('pack_path')->nullable();
            $table->string('pack_token', 64)->nullable()->unique();
            $table->timestamp('pack_generated_at')->nullable();
            $table->timestamp('pack_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('insurance_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'pack_status',
                'pack_path',
                'pack_token',
                'pack_generated_at',
                'pack_expires_at',
            ]);
        });
    }
};
