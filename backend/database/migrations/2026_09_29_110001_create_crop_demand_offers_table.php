<?php

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_demand_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crop_demand_id')->constrained('crop_demands')->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('price_per_kg', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->text('message')->nullable();
            $table->string('status', 20)->default(DemandOfferStatus::PENDING->value);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['crop_demand_id', 'status']);
            $table->index('farmer_id');
        });

        $blocking = implode("','", DemandOfferStatus::blockingNewOffer());
        DB::statement(
            "CREATE UNIQUE INDEX crop_demand_offers_one_active_per_farmer ON crop_demand_offers (crop_demand_id, farmer_id) WHERE status IN ('{$blocking}')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_demand_offers');
    }
};
