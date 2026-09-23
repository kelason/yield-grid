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
        Schema::table('purchases', function (Blueprint $table) {
            // Nullable because a purchase can be for a harvest listing or a forward contract
            $table->foreignId('harvest_listing_id')->nullable()->after('buyer_id')->constrained('harvest_listings')->cascadeOnDelete();

            // The original contract ID must become nullable to allow purchases tied to harvest listings
            // But SQLite doesn't easily drop constraints in this Laravel version without doctrine/dbal if it's already created as strict,
            // wait, we can just make it nullable.
            // Actually, Laravel 10 supports making foreign keys nullable if DB engine supports it.
            // To be safe with `change()`, let's just make it nullable.
            $table->foreignId('forward_contract_id')->nullable()->change();

            // Quantity this purchase covers (allows partial-quantity purchases)
            $table->decimal('quantity_kg', 10, 2)->nullable()->after('forward_contract_id');

            // Cash payment tracking
            $table->string('cash_payment_status', 20)->nullable()->after('payment_status');
            $table->decimal('cash_amount_confirmed', 12, 2)->default(0)->after('cash_payment_status');
            $table->timestamp('farmer_confirmed_at')->nullable()->after('cash_amount_confirmed');

            // Whether this is a downpayment (10%) or full payment
            $table->boolean('is_downpayment')->default(false)->after('farmer_confirmed_at');
            $table->decimal('total_contract_amount', 12, 2)->nullable()->after('is_downpayment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['harvest_listing_id']);
            $table->dropColumn([
                'harvest_listing_id',
                'quantity_kg',
                'cash_payment_status',
                'cash_amount_confirmed',
                'farmer_confirmed_at',
                'is_downpayment',
                'total_contract_amount',
            ]);

            // Reverting forward_contract_id to non-nullable might fail if there are null records,
            // but we leave it as is for down migration simplicity or try:
            // $table->foreignId('forward_contract_id')->nullable(false)->change();
        });
    }
};
