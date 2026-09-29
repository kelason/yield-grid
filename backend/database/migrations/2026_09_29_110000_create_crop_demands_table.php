<?php

use App\Domain\Marketplace\Enums\DemandStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_demands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('user_addresses')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('crop_name');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('remaining_quantity_kg', 10, 2);
            $table->decimal('target_price_per_kg', 10, 2);
            $table->decimal('total_budget', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->date('needed_by_date');
            $table->date('expiry_date');
            $table->string('status', 20)->default(DemandStatus::OPEN->value);
            $table->timestamps();

            $table->index(['status', 'expiry_date']);
            $table->index('buyer_id');
            $table->index('crop_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_demands');
    }
};
