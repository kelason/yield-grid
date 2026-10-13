<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumTag;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Community\Models\ThreadVote;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForumThreadSeeder extends Seeder
{
    private const PASSWORD = 'Testing123456!';

    /** @var array<string, array{name: string, role: UserRole}> */
    private const AUTHORS = [
        'farmer@example.com' => ['name' => 'Demo Farmer', 'role' => UserRole::FARMER],
        'buyer@example.com' => ['name' => 'Demo Buyer', 'role' => UserRole::BUYER],
        'yieldgridph@example.com' => ['name' => 'YieldGrid Admin', 'role' => UserRole::ADMIN],
    ];

    /** @var array<int, array{id: int, category: string, author: string, title: string, body: string, tags: list<string>, score: int, replies: int, pinned: bool, anonymous: bool}> */
    private const THREADS = [
        [
            'id' => 4,
            'category' => 'crop-help',
            'author' => 'farmer@example.com',
            'title' => 'Yellow leaves on rice seedlings — nitrogen deficiency?',
            'body' => 'My two-week-old rice seedlings in Nueva Ecija are turning yellow from the tip down. There are no standing water issues and I applied complete fertilizer at transplanting. Could this be nitrogen deficiency or tungro? Any advice before I spray anything?',
            'tags' => ['rice', 'fertilizer', 'disease'],
            'score' => 2,
            'replies' => 1,
            'pinned' => false,
            'anonymous' => true,
        ],
        [
            'id' => 5,
            'category' => 'general',
            'author' => 'farmer@example.com',
            'title' => 'Magandang ani! First successful dry-season harvest',
            'body' => 'Just finished my first dry-season harvest and the yield beat my target by a wide margin. Drip lines plus the new seed variety made the difference. Thank you to everyone who answered my irrigation questions last season.',
            'tags' => ['harvest', 'rice'],
            'score' => 2,
            'replies' => 2,
            'pinned' => false,
            'anonymous' => false,
        ],
        [
            'id' => 6,
            'category' => 'marketplace',
            'author' => 'farmer@example.com',
            'title' => 'How do forward contracts work for first-time sellers?',
            'body' => 'I am new to selling and a buyer offered me a forward contract for my next corn harvest. How does pricing normally work, and what should I check in the agreement before I sign?',
            'tags' => ['contract', 'market-price'],
            'score' => 1,
            'replies' => 1,
            'pinned' => false,
            'anonymous' => false,
        ],
        [
            'id' => 7,
            'category' => 'irrigation',
            'author' => 'farmer@example.com',
            'title' => 'Drip irrigation vs flood irrigation for a 1-hectare vegetable farm?',
            'body' => 'I am setting up irrigation for a 1-hectare vegetable farm in Laguna. Is drip irrigation worth the upfront cost over flood irrigation, and which pump size do small farms usually run?',
            'tags' => ['drip-irrigation', 'water-supply', 'vegetables'],
            'score' => 0,
            'replies' => 0,
            'pinned' => false,
            'anonymous' => false,
        ],
        [
            'id' => 8,
            'category' => 'pest-control',
            'author' => 'farmer@example.com',
            'title' => 'Stem borer pressure this season — least-spray options?',
            'body' => 'Stem borer pressure is heavy in our area this season. What least-spray options have worked for you — resistant varieties, pheromone traps, or adjusted planting schedules?',
            'tags' => ['pest-control', 'insects', 'rice'],
            'score' => 2,
            'replies' => 0,
            'pinned' => false,
            'anonymous' => false,
        ],
        [
            'id' => 9,
            'category' => 'success-stories',
            'author' => 'farmer@example.com',
            'title' => 'From 20 to 60 cavans: what changed this season',
            'body' => 'Sharing a win: our palay yield went from 20 to 60 cavans per hectare this season. The biggest drivers were certified seeds, split fertilizer application, and better water timing.',
            'tags' => ['harvest', 'rice'],
            'score' => 2,
            'replies' => 1,
            'pinned' => false,
            'anonymous' => false,
        ],
        [
            'id' => 10,
            'category' => 'general',
            'author' => 'yieldgridph@example.com',
            'title' => 'Welcome to the YieldGrid community forum — read first',
            'body' => 'Welcome to the YieldGrid community forum. Ask growing questions, share harvest results, and talk markets with fellow farmers and buyers. Please keep posts respectful, stay on topic per category, and never share personal contact details in public threads.',
            'tags' => [],
            'score' => 2,
            'replies' => 0,
            'pinned' => true,
            'anonymous' => false,
        ],
    ];

    /** @var array<int, array{id: int, thread: int, author: string, accepted: bool, body: string}> */
    private const REPLIES = [
        [
            'id' => 1,
            'thread' => 4,
            'author' => 'yieldgridph@example.com',
            'accepted' => true,
            'body' => 'Yellow tips at two weeks often point to nitrogen running low after flooding. Get a quick soil test from your municipal agriculture office first, then top-dress urea at 25 to 30 days after transplanting if they confirm it. Hold off on insecticide until you rule out nutrient deficiency.',
        ],
        [
            'id' => 2,
            'thread' => 5,
            'author' => 'buyer@example.com',
            'accepted' => false,
            'body' => 'Congratulations on the harvest. Which variety did you plant, and did you sell on contract or to spot buyers?',
        ],
        [
            'id' => 3,
            'thread' => 5,
            'author' => 'yieldgridph@example.com',
            'accepted' => false,
            'body' => 'Great result. If you share your fertilizer schedule, we can feature it in the next community growing guide.',
        ],
        [
            'id' => 4,
            'thread' => 6,
            'author' => 'buyer@example.com',
            'accepted' => false,
            'body' => 'As a buyer, here is how our forward contracts usually work: we agree on volume and a floor price before harvest, then adjust within a small band using the prevailing market price at delivery. Always confirm payment terms, quality specs, and who handles hauling.',
        ],
        [
            'id' => 5,
            'thread' => 9,
            'author' => 'buyer@example.com',
            'accepted' => false,
            'body' => 'Impressive jump. Was the gain mostly from irrigation timing or the new seed variety? I am looking for reliable palay suppliers next quarter.',
        ],
    ];

    /** @var array<int, list<string>> thread id => voter emails */
    private const VOTES = [
        4 => ['buyer@example.com', 'yieldgridph@example.com'],
        5 => ['buyer@example.com', 'yieldgridph@example.com'],
        6 => ['buyer@example.com'],
        8 => ['buyer@example.com', 'yieldgridph@example.com'],
        9 => ['buyer@example.com', 'yieldgridph@example.com'],
        10 => ['farmer@example.com', 'buyer@example.com'],
    ];

    public function run(): void
    {
        $this->call(ForumCategorySeeder::class);
        $this->call(ForumTagSeeder::class);

        $authors = $this->ensureAuthors();
        $this->clearPlaceholderActivity();
        $this->seedThreads($authors);
        $this->seedReplies($authors);
        $this->seedVotes($authors);
        $this->resetSequences();
    }

    /**
     * @return array<string, User>
     */
    private function ensureAuthors(): array
    {
        $authors = [];

        foreach (self::AUTHORS as $email => $attributes) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => $attributes['role'],
                ]
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $authors[$email] = $user;
        }

        return $authors;
    }

    private function clearPlaceholderActivity(): void
    {
        $ids = array_column(self::THREADS, 'id');

        ThreadVote::whereIn('thread_id', $ids)->delete();
        ForumReply::whereIn('thread_id', $ids)->forceDelete();
    }

    /**
     * @param  array<string, User>  $authors
     */
    private function seedThreads(array $authors): void
    {
        foreach (self::THREADS as $data) {
            // Explicit ids keep /dashboard/community/thread/{id} URLs stable;
            // forceFill bypasses $fillable which excludes the primary key.
            $thread = ForumThread::find($data['id']) ?? new ForumThread;
            $thread->forceFill([
                'id' => $data['id'],
                'user_id' => $authors[$data['author']]->id,
                'category_id' => ForumCategory::where('slug', $data['category'])->firstOrFail()->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'vote_score' => $data['score'],
                'reply_count' => $data['replies'],
                'is_pinned' => $data['pinned'],
                'is_anonymous' => $data['anonymous'],
                'last_activity_at' => now(),
            ])->save();

            $tagIds = ForumTag::whereIn('slug', $data['tags'])->pluck('id')->all();
            $thread->tags()->sync($tagIds);
        }
    }

    /**
     * @param  array<string, User>  $authors
     */
    private function seedReplies(array $authors): void
    {
        foreach (self::REPLIES as $data) {
            $reply = ForumReply::find($data['id']) ?? new ForumReply;
            $reply->forceFill([
                'id' => $data['id'],
                'thread_id' => $data['thread'],
                'user_id' => $authors[$data['author']]->id,
                'body' => $data['body'],
                'vote_score' => 0,
                'is_accepted' => $data['accepted'],
            ])->save();
        }

        ForumThread::where('id', 4)->update(['accepted_reply_id' => 1]);
    }

    /**
     * @param  array<string, User>  $authors
     */
    private function seedVotes(array $authors): void
    {
        foreach (self::VOTES as $threadId => $voters) {
            foreach ($voters as $email) {
                ThreadVote::updateOrCreate(
                    ['user_id' => $authors[$email]->id, 'thread_id' => $threadId],
                    ['value' => 1]
                );
            }
        }
    }

    private function resetSequences(): void
    {
        DB::statement("SELECT setval('public.forum_threads_id_seq', (SELECT MAX(id) FROM public.forum_threads))");
        DB::statement("SELECT setval('public.forum_replies_id_seq', (SELECT MAX(id) FROM public.forum_replies))");
    }
}
