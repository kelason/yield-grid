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
        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('forward_contract_id')->constrained()->cascadeOnDelete();
            $table->string('paymongo_payment_id')->nullable();
            $table->string('paymongo_checkout_id')->nullable()->unique();
            $table->string('payment_method', 20)->nullable();
            $table->decimal('amount_paid', 12, 2);
            $table->char('currency', 3)->default('PHP');
            $table->string('payment_status', 20)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('forward_contract_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
