<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description');
            $table->string('icon_emoji');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            // accepted_reply_id added later or nullable because replies depend on threads
            $table->unsignedBigInteger('accepted_reply_id')->nullable();

            $table->string('title');
            $table->text('body');
            $table->integer('vote_score')->default(0);
            $table->integer('reply_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'last_activity_at']);
            $table->index('user_id');
        });

        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('forum_replies')->cascadeOnDelete();

            $table->text('body');
            $table->integer('vote_score')->default(0);
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_anonymous')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['thread_id', 'created_at']);
            $table->index('user_id');
        });

        // Add foreign key constraint for accepted_reply_id
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreign('accepted_reply_id')->references('id')->on('forum_replies')->nullOnDelete();
        });

        Schema::create('forum_thread_tag', function (Blueprint $table) {
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('forum_tags')->cascadeOnDelete();
            $table->primary(['thread_id', 'tag_id']);
        });

        Schema::create('thread_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->smallInteger('value'); // 1 for upvote, -1 for downvote
            $table->timestamps();

            $table->unique(['user_id', 'thread_id']);
        });

        Schema::create('reply_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reply_id')->constrained('forum_replies')->cascadeOnDelete();
            $table->smallInteger('value');
            $table->timestamps();

            $table->unique(['user_id', 'reply_id']);
        });

        Schema::create('forum_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->unique(['user_id', 'reportable_type', 'reportable_id']);
        });

        Schema::create('forum_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id')->nullable(); // nullable until attached to a saved model
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->string('original_name');
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_attachments');
        Schema::dropIfExists('forum_reports');
        Schema::dropIfExists('reply_votes');
        Schema::dropIfExists('thread_votes');
        Schema::dropIfExists('forum_thread_tag');
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['accepted_reply_id']);
        });
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forum_tags');
        Schema::dropIfExists('forum_categories');
    }
};
