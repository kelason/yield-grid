<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_reference_prices', function (Blueprint $table): void {
            $table->id();
            $table->string('crop_slug', 100);
            $table->string('crop_display_name', 100);
            $table->string('tier', 20)->default(PriceTier::RETAIL->value);
            $table->decimal('price_per_kg', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->string('region_code', 20)->nullable();
            $table->string('market_name', 150)->nullable();
            $table->string('source', 30)->default(PriceSource::DA_BANTAY_PRESYO->value);
            $table->date('observed_at');
            $table->timestamps();

            $table->index(['crop_slug', 'region_code', 'observed_at']);
            $table->index(['crop_slug', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_reference_prices');
    }
};
