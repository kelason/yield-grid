<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $codeColumns = [
        'region_code',
        'province_code',
        'city_municipality_code',
        'barangay_code',
    ];

    public function up(): void
    {
        foreach ($this->codeColumns as $column) {
            DB::statement("ALTER TABLE user_addresses ALTER COLUMN {$column} TYPE varchar(12)");
        }
    }

    public function down(): void
    {
        foreach ($this->codeColumns as $column) {
            DB::statement("ALTER TABLE user_addresses ALTER COLUMN {$column} TYPE varchar(10)");
        }
    }
};
