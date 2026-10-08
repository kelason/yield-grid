<?php

use App\Admin\Resources\AdminContentResource;
use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\AdminActionLog;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function adcAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function adcTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('adc-test-token')->plainTextToken;
}

function adcThread(User $owner, array $overrides = []): ForumThread
{
    $suffix = uniqid();

    $category = ForumCategory::create([
        'name' => 'Browsing '.$suffix,
        'slug' => 'browsing-'.$suffix,
        'description' => 'Admin content tests',
        'icon_emoji' => '🧭',
        'sort_order' => 1,
    ]);

    return ForumThread::create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'A browsable thread title',
        'body' => 'Thread body that is long enough for content tests.',
        'last_activity_at' => now(),
    ], $overrides));
}

function adcReply(ForumThread $thread, User $owner, array $overrides = []): ForumReply
{
    return ForumReply::create(array_merge([
        'thread_id' => $thread->id,
        'user_id' => $owner->id,
        'body' => 'A reply body that is long enough for content tests.',
    ], $overrides));
}

function adcContract(User $farmer, array $overrides = []): ForwardContract
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Browse Farm '.$farmer->id.uniqid()]);
    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10,
    ]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);

    return ForwardContract::factory()->create(array_merge([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ], $overrides));
}

function adcListing(User $farmer, array $overrides = []): HarvestListing
{
    return HarvestListing::create(array_merge([
        'farmer_id' => $farmer->id,
        'title' => 'Fresh rice for sale',
        'description' => 'Newly harvested rice, ready for pickup.',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'status' => ContractStatus::AVAILABLE,
    ], $overrides));
}

function adcDemand(User $buyer, array $overrides = []): CropDemand
{
    return ReverseMarketplaceHelper::makeDemand($buyer, null, $overrides);
}

it('denies guests on the content moderation endpoints', function () {
    $thread = adcThread(User::factory()->farmer()->create());

    $this->getJson('/api/v1/admin/content/thread')->assertUnauthorized();
    $this->getJson("/api/v1/admin/content/thread/{$thread->id}")->assertUnauthorized();
    $this->getJson('/api/v1/admin/content/thread/999999')->assertUnauthorized();
    $this->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'Spam.'])->assertUnauthorized();
    $this->postJson("/api/v1/admin/content/thread/{$thread->id}/restore", ['reason' => 'Appeal.'])->assertUnauthorized();
});

it('denies farmers and buyers on the content endpoints including missing ids', function () {
    $thread = adcThread(User::factory()->farmer()->create());
    $token = adcTokenFor(User::factory()->farmer()->create());

    $this->withToken($token)->getJson('/api/v1/admin/content/thread')->assertForbidden();
    $this->withToken($token)->getJson("/api/v1/admin/content/thread/{$thread->id}")->assertForbidden();
    $this->withToken($token)->getJson('/api/v1/admin/content/thread/999999')->assertForbidden();
    $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'Spam.'])->assertForbidden();
    $this->withToken($token)->postJson('/api/v1/admin/content/contract/999999/restore', ['reason' => 'Appeal.'])->assertForbidden();

    $buyerToken = adcTokenFor(User::factory()->buyer()->create());
    $this->withToken($buyerToken)->getJson('/api/v1/admin/content/demand')->assertForbidden();
});

it('denies unverified admins on the content endpoints', function () {
    $thread = adcThread(User::factory()->farmer()->create());
    $token = adcTokenFor(User::factory()->unverified()->create(['role' => 'admin']));

    $this->withToken($token)->getJson('/api/v1/admin/content/thread')->assertForbidden();
    $this->withToken($token)->getJson("/api/v1/admin/content/thread/{$thread->id}")->assertForbidden();
    $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'Spam.'])->assertForbidden();
});

it('rejects unknown content types without matching a route', function () {
    $token = adcTokenFor(adcAdmin());

    $this->withToken($token)->getJson('/api/v1/admin/content/user')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/admin/content/user/7')->assertNotFound();
    $this->withToken($token)->postJson('/api/v1/admin/content/user/7/hide', ['reason' => 'Spam.'])->assertNotFound();
});

it('lists each content type with pagination and private caching', function () {
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $thread = adcThread($farmer);
    $reply = adcReply($thread, $farmer);
    $contract = adcContract($farmer);
    $listing = adcListing($farmer);
    $demand = adcDemand($buyer);
    $token = adcTokenFor(adcAdmin());

    $threads = $this->withToken($token)->getJson('/api/v1/admin/content/thread');
    $threads->assertOk();
    $threads->assertHeader('Cache-Control', 'no-store, private');
    expect(array_keys($threads->json()))->toContain('data', 'links', 'meta');
    expect(collect($threads->json('data'))->pluck('id'))->toContain((string) $thread->id);
    expect($threads->json('data.0.type'))->toBe('thread');

    foreach ([
        'reply' => $reply->id,
        'contract' => $contract->id,
        'listing' => $listing->id,
        'demand' => $demand->id,
    ] as $type => $id) {
        $response = $this->withToken($token)->getJson("/api/v1/admin/content/{$type}");
        $response->assertOk();
        expect(collect($response->json('data'))->pluck('id'))->toContain((string) $id);
        expect($response->json('data.0.type'))->toBe($type);
    }
});

it('paginates content and validates page bounds', function () {
    $farmer = User::factory()->farmer()->create();

    for ($i = 0; $i < 3; $i++) {
        adcThread($farmer, ['title' => "Paginated thread number {$i} here"]);
    }

    $token = adcTokenFor(adcAdmin());

    $first = $this->withToken($token)->getJson('/api/v1/admin/content/thread?per_page=2');
    $first->assertOk();
    expect($first->json('data'))->toHaveCount(2);
    expect($first->json('meta.total'))->toBe(3);
    expect($first->json('meta.per_page'))->toBe(2);

    $second = $this->withToken($token)->getJson('/api/v1/admin/content/thread?per_page=2&page=2');
    $second->assertOk();
    expect($second->json('data'))->toHaveCount(1);

    $this->withToken($token)->getJson('/api/v1/admin/content/thread?per_page=0')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/content/thread?per_page=101')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/content/thread?page=0')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/content/thread?page=10001')->assertUnprocessable();
});

it('searches content titles without interpreting wildcards', function () {
    $farmer = User::factory()->farmer()->create();
    adcThread($farmer, ['title' => 'Sunflower harvest guide here']);
    adcThread($farmer, ['title' => 'Rice milling basics guide']);
    $percent = adcThread($farmer, ['title' => '100% organic rice sale']);
    adcThread($farmer, ['title' => 'Organic rice price watch']);
    $token = adcTokenFor(adcAdmin());

    $match = $this->withToken($token)->getJson('/api/v1/admin/content/thread?search=sunflower');
    $match->assertOk();
    expect(collect($match->json('data'))->pluck('title')->all())->toBe(['Sunflower harvest guide here']);

    $none = $this->withToken($token)->getJson('/api/v1/admin/content/thread?search=zzz-no-such-crop');
    $none->assertOk();
    expect($none->json('data'))->toBe([]);

    $literal = $this->withToken($token)->getJson('/api/v1/admin/content/thread?search='.urlencode('%'));
    $literal->assertOk();
    expect(collect($literal->json('data'))->pluck('id')->all())->toBe([(string) $percent->id]);

    $this->withToken($token)
        ->getJson('/api/v1/admin/content/thread?search='.str_repeat('s', 101))
        ->assertUnprocessable();
});

it('searches contracts through the delegated repository query', function () {
    $farmer = User::factory()->farmer()->create();
    adcContract($farmer, ['title' => 'Jasmine rice forward sale', 'crop_name' => 'Jasmine Rice']);
    adcContract($farmer, ['title' => 'Yellow corn forward sale', 'crop_name' => 'Yellow Corn']);
    $token = adcTokenFor(adcAdmin());

    $response = $this->withToken($token)->getJson('/api/v1/admin/content/contract?search=corn');
    $response->assertOk();
    expect(collect($response->json('data'))->pluck('crop_name')->all())->toBe(['Yellow Corn']);
});

it('filters threads by direct visibility while listing everything by default', function () {
    $admin = adcAdmin();
    $farmer = User::factory()->farmer()->create();
    $visible = adcThread($farmer, ['title' => 'Visible thread for filter']);
    $hidden = adcThread($farmer, ['title' => 'Hidden thread for filter']);
    $token = adcTokenFor($admin);

    app(ModerateForumContentAction::class)->execute($admin, ReportTargetType::THREAD, (string) $hidden->id, true, 'Spam.');

    $all = $this->withToken($token)->getJson('/api/v1/admin/content/thread');
    expect(collect($all->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([(string) $visible->id, (string) $hidden->id])->sort()->values()->all());

    $onlyVisible = $this->withToken($token)->getJson('/api/v1/admin/content/thread?visibility=visible');
    expect(collect($onlyVisible->json('data'))->pluck('id')->all())->toBe([(string) $visible->id]);

    $onlyHidden = $this->withToken($token)->getJson('/api/v1/admin/content/thread?visibility=hidden');
    expect(collect($onlyHidden->json('data'))->pluck('id')->all())->toBe([(string) $hidden->id]);

    $this->withToken($token)->getJson('/api/v1/admin/content/thread?visibility=archived')->assertUnprocessable();
});

it('treats root-suppressed clones as hidden and ancestor-suppressed replies as unflagged', function () {
    $admin = adcAdmin();
    $farmer = User::factory()->farmer()->create();
    $token = adcTokenFor($admin);

    $root = adcContract($farmer);
    $clone = adcContract($farmer);
    $clone->forceFill(['moderation_root_id' => $root->id])->save();

    app(ModerateMarketplaceContentAction::class)->execute($admin, ReportTargetType::CONTRACT, (string) $root->id, true, 'Fraud.');

    $hiddenContracts = $this->withToken($token)->getJson('/api/v1/admin/content/contract?visibility=hidden');
    expect(collect($hiddenContracts->json('data'))->pluck('id'))->toContain((string) $clone->id);

    $visibleContracts = $this->withToken($token)->getJson('/api/v1/admin/content/contract?visibility=visible');
    expect(collect($visibleContracts->json('data'))->pluck('id'))->not->toContain((string) $clone->id);

    $thread = adcThread($farmer);
    $reply = adcReply($thread, $farmer);
    app(ModerateForumContentAction::class)->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, 'Spam.');

    // Forum visibility filters track the direct flag: a suppressed but
    // unflagged reply is not independently actionable.
    $hiddenReplies = $this->withToken($token)->getJson('/api/v1/admin/content/reply?visibility=hidden');
    expect(collect($hiddenReplies->json('data'))->pluck('id'))->not->toContain((string) $reply->id);
});

it('orders content newest first with a deterministic id tie-break', function () {
    $farmer = User::factory()->farmer()->create();
    $oldest = adcThread($farmer, ['title' => 'Oldest thread in order']);
    $middle = adcThread($farmer, ['title' => 'Middle thread in order']);
    $newest = adcThread($farmer, ['title' => 'Newest thread in order']);

    $oldest->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();
    $middle->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)])->save();
    $newest->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

    $token = adcTokenFor(adcAdmin());

    $response = $this->withToken($token)->getJson('/api/v1/admin/content/thread');
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toBe([(string) $newest->id, (string) $middle->id, (string) $oldest->id]);
});

it('shows content detail and 404s on missing or malformed ids', function () {
    $farmer = User::factory()->farmer()->create();
    $thread = adcThread($farmer);
    $token = adcTokenFor(adcAdmin());

    $response = $this->withToken($token)->getJson("/api/v1/admin/content/thread/{$thread->id}");
    $response->assertOk();
    $response->assertHeader('Cache-Control', 'no-store, private');

    $data = $response->json('data');
    expect($data['id'])->toBe((string) $thread->id);
    expect($data['type'])->toBe('thread');
    expect($data['title'])->toBe($thread->title);
    expect($data['author'])->toBe([
        'id' => (string) $farmer->id,
        'name' => $farmer->name,
        'email' => $farmer->email,
    ]);
    expect($data['is_hidden'])->toBeFalse();

    $this->withToken($token)->getJson('/api/v1/admin/content/thread/999999')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/admin/content/thread/abc')->assertNotFound();
});

it('renders null authors safely', function () {
    // Content author foreign keys cascade, so a missing author cannot be
    // produced through deletion; the resource still guards the null path.
    $thread = adcThread(User::factory()->farmer()->create());
    $thread->setRelation('author', null);

    $threadData = (new AdminContentResource($thread))->toArray(request());
    expect($threadData['author'])->toBeNull();
    expect($threadData['id'])->toBe((string) $thread->id);

    $contract = adcContract(User::factory()->farmer()->create());
    $contract->setRelation('farmer', null);

    $contractData = (new AdminContentResource($contract))->toArray(request());
    expect($contractData['author'])->toBeNull();
});

it('performs no mutation when browsing content through GET', function () {
    $farmer = User::factory()->farmer()->create();
    $thread = adcThread($farmer);
    $token = adcTokenFor(adcAdmin());

    $this->withToken($token)->getJson('/api/v1/admin/content/thread?visibility=visible')->assertOk();
    $this->withToken($token)->getJson("/api/v1/admin/content/thread/{$thread->id}")->assertOk();

    expect($thread->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::count())->toBe(0);
});

it('hides and restores a thread with reason bounds and repeat conflicts', function () {
    $admin = adcAdmin();
    $thread = adcThread(User::factory()->farmer()->create());
    $token = adcTokenFor($admin);

    $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", [])->assertUnprocessable();
    $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => ''])->assertUnprocessable();
    $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => str_repeat('r', 501)])->assertUnprocessable();
    expect($thread->fresh()->hidden_at)->toBeNull();

    $hidden = $this->withToken($token)->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'x']);
    $hidden->assertOk();
    expect($hidden->json('data.is_hidden'))->toBeTrue();
    expect($hidden->json('data.hidden_by'))->toBe((string) $admin->id);
    expect($hidden->json('data.hidden_reason'))->toBe('x');
    expect($hidden->json('meta.selected_id'))->toBe((string) $thread->id);
    expect($hidden->json('meta.affected_root_id'))->toBe((string) $thread->id);

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'Again.'])
        ->assertConflict();

    $restored = $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/restore", ['reason' => str_repeat('r', 500)]);
    $restored->assertOk();
    expect($restored->json('data.is_hidden'))->toBeFalse();
    expect($thread->fresh()->hidden_at)->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/restore", ['reason' => 'Again.'])
        ->assertConflict();

    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(1);
    expect(AdminActionLog::where('action', AdminAction::CONTENT_RESTORED->value)->count())->toBe(1);
});

it('hides and restores a reply directly', function () {
    $admin = adcAdmin();
    $owner = User::factory()->farmer()->create();
    $reply = adcReply(adcThread($owner), $owner);
    $token = adcTokenFor($admin);

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/reply/{$reply->id}/hide", ['reason' => 'Spam reply.'])
        ->assertOk();
    expect($reply->fresh()->hidden_at)->not->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/reply/{$reply->id}/restore", ['reason' => 'Appeal upheld.'])
        ->assertOk();
    expect($reply->fresh()->hidden_at)->toBeNull();
});

it('resolves the canonical root when moderating a split contract', function () {
    $admin = adcAdmin();
    $farmer = User::factory()->farmer()->create();
    $root = adcContract($farmer);
    $clone = adcContract($farmer);
    $clone->forceFill(['moderation_root_id' => $root->id])->save();
    $token = adcTokenFor($admin);

    $hidden = $this->withToken($token)
        ->postJson("/api/v1/admin/content/contract/{$clone->id}/hide", ['reason' => 'Fraud ring.']);
    $hidden->assertOk();
    expect($hidden->json('data.id'))->toBe((string) $root->id);
    expect($hidden->json('meta.selected_id'))->toBe((string) $clone->id);
    expect($hidden->json('meta.affected_root_id'))->toBe((string) $root->id);

    expect($root->fresh()->hidden_at)->not->toBeNull();
    expect($clone->fresh()->hidden_at)->toBeNull();
    expect($clone->fresh()->isEffectivelyHidden())->toBeTrue();

    $restored = $this->withToken($token)
        ->postJson("/api/v1/admin/content/contract/{$clone->id}/restore", ['reason' => 'Cleared.']);
    $restored->assertOk();
    expect($restored->json('meta.affected_root_id'))->toBe((string) $root->id);
    expect($root->fresh()->hidden_at)->toBeNull();
    expect($clone->fresh()->isEffectivelyHidden())->toBeFalse();
});

it('resolves the canonical root when moderating a split listing', function () {
    $admin = adcAdmin();
    $farmer = User::factory()->farmer()->create();
    $root = adcListing($farmer);
    $clone = adcListing($farmer);
    $clone->forceFill(['moderation_root_id' => $root->id])->save();
    $token = adcTokenFor($admin);

    $hidden = $this->withToken($token)
        ->postJson("/api/v1/admin/content/listing/{$clone->id}/hide", ['reason' => 'Prohibited item.']);
    $hidden->assertOk();
    expect($hidden->json('data.id'))->toBe((string) $root->id);
    expect($hidden->json('meta.selected_id'))->toBe((string) $clone->id);
    expect($hidden->json('meta.affected_root_id'))->toBe((string) $root->id);

    expect($root->fresh()->hidden_at)->not->toBeNull();
    expect($clone->fresh()->hidden_at)->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/listing/{$clone->id}/restore", ['reason' => 'Cleared.'])
        ->assertOk();
    expect($root->fresh()->hidden_at)->toBeNull();
});

it('hides and restores a demand without lineage fields', function () {
    $admin = adcAdmin();
    $demand = adcDemand(User::factory()->buyer()->create());
    $token = adcTokenFor($admin);

    $hidden = $this->withToken($token)
        ->postJson("/api/v1/admin/content/demand/{$demand->id}/hide", ['reason' => 'Suspected fraud.']);
    $hidden->assertOk();
    expect($hidden->json('data.is_hidden'))->toBeTrue();
    expect($hidden->json('data.status'))->toBe($demand->status->value);
    expect($hidden->json('data'))->not->toHaveKey('moderation_root_id');
    expect($demand->fresh()->hidden_at)->not->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/demand/{$demand->id}/restore", ['reason' => 'Cleared.'])
        ->assertOk();
    expect($demand->fresh()->hidden_at)->toBeNull();
});

it('returns 404 when hiding or restoring missing targets', function () {
    $token = adcTokenFor(adcAdmin());

    $this->withToken($token)->postJson('/api/v1/admin/content/thread/999999/hide', ['reason' => 'Spam.'])->assertNotFound();
    $this->withToken($token)->postJson('/api/v1/admin/content/contract/999999/restore', ['reason' => 'Appeal.'])->assertNotFound();
    $this->withToken($token)->postJson('/api/v1/admin/content/demand/999999/hide', ['reason' => 'Spam.'])->assertNotFound();
});
