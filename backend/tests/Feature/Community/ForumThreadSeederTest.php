<?php

use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Community\Models\ThreadVote;
use Database\Seeders\ForumThreadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('seeds YieldGrid sample threads without placeholder content', function () {
    (new ForumThreadSeeder)->run();

    expect(ForumThread::where('title', 'hello world')->exists())->toBeFalse();
    expect(ForumThread::where('title', 'Goodbye RareJob')->exists())->toBeFalse();

    $threads = ForumThread::orderBy('id')->get();
    expect($threads)->toHaveCount(7);
    expect($threads->pluck('title'))->toContain('Yellow leaves on rice seedlings — nitrogen deficiency?');

    $answered = ForumThread::with('acceptedReply')->find(4);
    expect($answered->reply_count)->toBe(1);
    expect($answered->acceptedReply)->not->toBeNull();
    expect($answered->acceptedReply->is_accepted)->toBeTrue();

    expect($threads->find(5)->tags->pluck('slug')->sort()->values()->all())
        ->toBe(['harvest', 'rice']);

    foreach ($threads as $thread) {
        expect(ForumReply::where('thread_id', $thread->id)->count())->toBe($thread->reply_count);
        expect(ThreadVote::where('thread_id', $thread->id)->count())->toBe($thread->vote_score);
    }
});

it('keeps forum samples idempotent across runs', function () {
    (new ForumThreadSeeder)->run();
    (new ForumThreadSeeder)->run();

    expect(ForumThread::count())->toBe(7);
    expect(ForumReply::count())->toBe(5);
});
