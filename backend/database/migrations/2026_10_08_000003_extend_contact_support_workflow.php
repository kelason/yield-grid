<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string STATUS_CHECK = 'contact_messages_status_check';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE contact_messages DROP CONSTRAINT IF EXISTS '.self::STATUS_CHECK);
            DB::statement(
                'ALTER TABLE contact_messages ADD CONSTRAINT '.self::STATUS_CHECK
                ." CHECK (status IN ('unread', 'read', 'replied', 'closed'))"
            );
        }

        Schema::create('contact_message_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('contact_messages')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 255);
            $table->text('body');
            $table->string('client_request_id', 64);
            $table->string('delivery_status', 16)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sending_started_at')->nullable();
            $table->unsignedInteger('delivery_generation')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'client_request_id']);
            $table->index(['message_id', 'delivery_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $closed = DB::table('contact_messages')->where('status', 'closed')->count();

            if ($closed > 0) {
                throw new RuntimeException(
                    "Cannot roll back: {$closed} contact message(s) use status 'closed'. Use a forward migration instead."
                );
            }
        }

        Schema::dropIfExists('contact_message_replies');

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE contact_messages DROP CONSTRAINT IF EXISTS '.self::STATUS_CHECK);
        DB::statement(
            'ALTER TABLE contact_messages ADD CONSTRAINT '.self::STATUS_CHECK
            ." CHECK (status IN ('unread', 'read', 'replied'))"
        );
    }
};
