<?php

use App\Domain\Marketplace\Models\CropPriceAlias;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_price_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('alias_slug', 100)->unique();
            $table->string('crop_slug', 100);
            $table->string('source', 20)->default(CropPriceAlias::SOURCE_SEED);
            $table->boolean('verified')->default(true);
            $table->timestamps();

            $table->index('crop_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_price_aliases');
    }
};
