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
        Schema::create('forward_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crop_recommendation_id')->constrained()->cascadeOnDelete();
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
            $table->timestamps();

            $table->index(['status', 'estimated_harvest_date']);
            $table->index('farmer_id');
            $table->index('crop_recommendation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forward_contracts');
    }
};
