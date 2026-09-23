<?php

use App\Domain\Marketplace\Enums\ContractStatus;
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
        Schema::create('harvest_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('crop_name');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('price_per_kg', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->date('estimated_harvest_date');
            $table->date('expiry_date');
            $table->string('status', 20)->default(ContractStatus::AVAILABLE->value);
            $table->unsignedInteger('shelf_life_days')->nullable();
            $table->boolean('is_harvest_available')->default(false);
            $table->timestamps();

            $table->index(['is_harvest_available', 'status', 'estimated_harvest_date'], 'hl_availability_idx');
            $table->index('farmer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvest_listings');
    }
};
