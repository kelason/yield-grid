<?php

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Shared\Models\ContentReport;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Contact\Models\ContactMessage;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function aoAdminToken(): string
{
    Auth::forgetGuards();

    $admin = User::factory()->create(['role' => 'admin']);

    return $admin->createToken('admin-overview-test-token')->plainTextToken;
}

function aoTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('admin-overview-test-token')->plainTextToken;
}

function aoThread(User $owner, array $overrides = []): ForumThread
{
    $suffix = uniqid();

    $category = ForumCategory::create([
        'name' => 'Overview '.$suffix,
        'slug' => 'overview-'.$suffix,
        'description' => 'Admin overview tests',
        'icon_emoji' => '🧭',
        'sort_order' => 1,
    ]);

    $thread = ForumThread::create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'An overview thread title',
        'body' => 'Thread body that is long enough for overview tests.',
        'last_activity_at' => now(),
    ], $overrides));

    return aoHide($thread, $overrides);
}

function aoReply(ForumThread $thread, User $owner, array $overrides = []): ForumReply
{
    $reply = ForumReply::create(array_merge([
        'thread_id' => $thread->id,
        'user_id' => $owner->id,
        'body' => 'A reply body that is long enough for overview tests.',
    ], $overrides));

    return aoHide($reply, $overrides);
}

function aoContract(User $farmer, array $overrides = []): ForwardContract
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Overview Farm '.$farmer->id.uniqid()]);
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

    $contract = ForwardContract::factory()->create(array_merge([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ], $overrides));

    return aoHide($contract, $overrides);
}

function aoListing(User $farmer, array $overrides = []): HarvestListing
{
    $listing = HarvestListing::create(array_merge([
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

    return aoHide($listing, $overrides);
}

function aoDemand(User $buyer, array $overrides = []): CropDemand
{
    $demand = ReverseMarketplaceHelper::makeDemand($buyer, null, $overrides);

    return aoHide($demand, $overrides);
}

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @param  T  $model
 * @return T
 */
function aoHide($model, array $overrides)
{
    $moderation = array_intersect_key($overrides, [
        'hidden_at' => true,
        'hidden_by' => true,
        'hidden_reason' => true,
        'moderation_root_id' => true,
    ]);

    if ($moderation !== []) {
        $model->forceFill($moderation)->save();
    }

    return $model;
}

function aoSuspend(User $user): User
{
    $user->forceFill([
        'suspended_at' => now(),
        'suspended_reason' => 'Overview fixture suspension.',
    ])->save();

    return $user;
}

function aoReport(User $reporter, ReportTargetType $type, int $targetId, array $overrides = []): ContentReport
{
    return ContentReport::create(array_merge([
        'user_id' => $reporter->id,
        'reportable_type' => $type->value,
        'reportable_id' => $targetId,
        'reason' => ContentReportReason::SPAM->value,
        'status' => ContentReportStatus::OPEN->value,
    ], $overrides));
}

function aoMessage(array $overrides = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'June Inquirer',
        'email' => 'june-'.uniqid().'@example.com',
        'subject' => 'Harvest question',
        'message' => 'When is the next harvest?',
        'status' => ContactStatus::UNREAD->value,
    ], $overrides));
}

function aoMessageReply(ContactMessage $message, array $overrides = []): ContactMessageReply
{
    return ContactMessageReply::create(array_merge([
        'message_id' => $message->id,
        'admin_id' => null,
        'recipient' => $message->email,
        'body' => 'Thanks for writing to us.',
        'client_request_id' => (string) Str::uuid(),
        'delivery_status' => ReplyDeliveryStatus::QUEUED->value,
        'attempts' => 0,
        'delivery_generation' => 0,
    ], $overrides));
}

function aoIssue(User $reporter, array $overrides = []): IssueTicket
{
    return IssueTicket::create(array_merge([
        'user_id' => $reporter->id,
        'category' => IssueCategory::TECHNICAL->value,
        'subject' => 'Checkout button does nothing',
        'description' => 'Tapping checkout never advances.',
        'page_path' => '/dashboard/marketplace',
        'status' => IssueStatus::OPEN->value,
        'client_request_id' => (string) Str::uuid(),
    ], $overrides));
}

it('denies guests, members, and unverified admins on the overview endpoint', function () {
    $this->getJson('/api/v1/admin/overview')->assertUnauthorized();

    $farmerToken = aoTokenFor(User::factory()->farmer()->create());
    $this->withToken($farmerToken)->getJson('/api/v1/admin/overview')->assertForbidden();

    $buyerToken = aoTokenFor(User::factory()->buyer()->create());
    $this->withToken($buyerToken)->getJson('/api/v1/admin/overview')->assertForbidden();

    $unverifiedToken = aoTokenFor(User::factory()->unverified()->create(['role' => 'admin']));
    $this->withToken($unverifiedToken)->getJson('/api/v1/admin/overview')->assertForbidden();
});

it('returns database-wide counts with effective visibility semantics', function () {
    $token = aoAdminToken();
    User::factory()->create(['role' => 'admin']);

    $farmers = [
        User::factory()->farmer()->create(),
        User::factory()->farmer()->create(),
        aoSuspend(User::factory()->farmer()->create()),
    ];
    $buyers = [
        User::factory()->buyer()->create(),
        aoSuspend(User::factory()->buyer()->create()),
    ];

    $visibleThreads = [];
    for ($i = 0; $i < 9; $i++) {
        $visibleThreads[] = aoThread($farmers[$i % 3]);
    }
    $hiddenThreads = [aoThread($farmers[0], ['hidden_at' => now()]), aoThread($buyers[0], ['hidden_at' => now()])];
    $deletedThreads = [aoThread($farmers[1]), aoThread($buyers[0])];
    foreach ($deletedThreads as $thread) {
        $thread->delete();
    }

    $visibleThread = $visibleThreads[0];
    $hiddenThread = $hiddenThreads[0];
    $deletedThread = $deletedThreads[0];

    aoReply($visibleThread, $buyers[0]);
    aoReply($visibleThread, $farmers[0], ['hidden_at' => now()]);
    $hiddenParent = aoReply($visibleThread, $farmers[1], ['hidden_at' => now()]);
    aoReply($visibleThread, $buyers[0], ['parent_id' => $hiddenParent->id]);
    $shownParent = aoReply($visibleThread, $farmers[0]);
    aoReply($visibleThread, $buyers[0], ['parent_id' => $shownParent->id]);
    aoReply($hiddenThread, $buyers[0]);
    aoReply($hiddenThread, $farmers[0], ['hidden_at' => now()]);
    aoReply($deletedThread, $buyers[0]);
    $trashedReply = aoReply($visibleThread, $farmers[1]);
    $trashedReply->delete();

    $contractRoot = aoContract($farmers[0]);
    $hiddenContractRoot = aoContract($farmers[1], ['hidden_at' => now()]);
    aoContract($farmers[0], ['moderation_root_id' => $contractRoot->id]);
    aoContract($farmers[1], ['moderation_root_id' => $hiddenContractRoot->id]);
    aoContract($farmers[0], ['moderation_root_id' => $contractRoot->id, 'hidden_at' => now()]);

    $listingRoot = aoListing($farmers[0]);
    $hiddenListingRoot = aoListing($farmers[1], ['hidden_at' => now()]);
    aoListing($farmers[0], ['moderation_root_id' => $hiddenListingRoot->id]);
    aoListing($farmers[0], ['moderation_root_id' => $listingRoot->id]);

    aoDemand($buyers[0]);
    aoDemand($buyers[0], ['hidden_at' => now()]);
    aoDemand($buyers[0]);

    aoMessage(['status' => ContactStatus::UNREAD->value]);
    aoMessage(['status' => ContactStatus::UNREAD->value]);
    aoMessage(['status' => ContactStatus::READ->value]);
    aoMessage(['status' => ContactStatus::REPLIED->value]);
    $closedMessage = aoMessage(['status' => ContactStatus::CLOSED->value]);

    aoMessageReply(aoMessage(['status' => ContactStatus::UNREAD->value]), ['delivery_status' => ReplyDeliveryStatus::FAILED->value]);
    aoMessageReply(aoMessage(['status' => ContactStatus::READ->value]), ['delivery_status' => ReplyDeliveryStatus::SENT->value]);
    aoMessageReply($closedMessage, ['delivery_status' => ReplyDeliveryStatus::FAILED->value]);
    aoMessageReply(aoMessage(['status' => ContactStatus::REPLIED->value]), ['delivery_status' => ReplyDeliveryStatus::SENT->value]);
    aoMessageReply(aoMessage(['status' => ContactStatus::UNREAD->value]), ['delivery_status' => ReplyDeliveryStatus::QUEUED->value]);

    $reporters = [User::factory()->farmer()->create(), User::factory()->buyer()->create()];
    aoReport($reporters[0], ReportTargetType::THREAD, $visibleThreads[1]->id);
    aoReport($reporters[1], ReportTargetType::THREAD, $visibleThreads[1]->id);
    aoReport($reporters[0], ReportTargetType::REPLY, 999001, ['status' => ContentReportStatus::REVIEWING->value]);
    aoReport($reporters[1], ReportTargetType::LISTING, 999002, ['status' => ContentReportStatus::RESOLVED->value]);
    aoReport($reporters[0], ReportTargetType::DEMAND, 999003, ['status' => ContentReportStatus::DISMISSED->value]);

    aoIssue($buyers[0]);
    aoIssue($farmers[0]);
    aoIssue($buyers[0], ['status' => IssueStatus::IN_PROGRESS->value]);
    aoIssue($farmers[1], ['status' => IssueStatus::RESOLVED->value, 'resolution' => 'Fixed the checkout button.']);
    aoIssue($buyers[0], ['status' => IssueStatus::CLOSED->value, 'resolution' => 'Duplicate ticket.']);

    $response = $this->withToken($token)->getJson('/api/v1/admin/overview');

    $response->assertOk();
    $response->assertHeader('Cache-Control', 'no-store, private');
    $response->assertJsonStructure([
        'data' => [
            'users' => ['members_total', 'members_suspended'],
            'content' => [
                'thread' => ['total', 'visible', 'hidden'],
                'reply' => ['total', 'visible', 'hidden'],
                'contract' => ['total', 'visible', 'hidden'],
                'listing' => ['total', 'visible', 'hidden'],
                'demand' => ['total', 'visible', 'hidden'],
            ],
            'inquiries' => ['unread', 'read', 'replied', 'closed', 'failed_replies'],
            'reports' => ['open', 'reviewing', 'resolved', 'dismissed'],
            'issues' => ['open', 'in_progress', 'resolved', 'closed'],
            'generated_at',
        ],
    ]);

    $payload = $response->json('data');

    expect($payload['users'])->toBe(['members_total' => 7, 'members_suspended' => 2]);
    expect($payload['content']['thread'])->toBe(['total' => 11, 'visible' => 9, 'hidden' => 2]);
    expect($payload['content']['reply'])->toBe(['total' => 9, 'visible' => 3, 'hidden' => 6]);
    expect($payload['content']['contract'])->toBe(['total' => 5, 'visible' => 2, 'hidden' => 3]);
    expect($payload['content']['listing'])->toBe(['total' => 4, 'visible' => 2, 'hidden' => 2]);
    expect($payload['content']['demand'])->toBe(['total' => 3, 'visible' => 2, 'hidden' => 1]);
    expect($payload['inquiries'])->toBe(['unread' => 4, 'read' => 2, 'replied' => 2, 'closed' => 1, 'failed_replies' => 2]);
    expect($payload['reports'])->toBe(['open' => 2, 'reviewing' => 1, 'resolved' => 1, 'dismissed' => 1]);
    expect($payload['issues'])->toBe(['open' => 2, 'in_progress' => 1, 'resolved' => 1, 'closed' => 1]);

    foreach ($payload['content'] as $bucket) {
        expect($bucket['total'])->toBe($bucket['visible'] + $bucket['hidden']);
    }

    expect($payload['generated_at'])->toBeString()->not->toBe('');
});

it('counts failed replies independently of inquiry status', function () {
    $token = aoAdminToken();

    $closed = aoMessage(['status' => ContactStatus::CLOSED->value]);
    aoMessageReply($closed, ['delivery_status' => ReplyDeliveryStatus::FAILED->value]);

    $unread = aoMessage(['status' => ContactStatus::UNREAD->value]);
    aoMessageReply($unread, ['delivery_status' => ReplyDeliveryStatus::SENT->value]);

    $payload = $this->withToken($token)->getJson('/api/v1/admin/overview')->json('data');

    expect($payload['inquiries']['failed_replies'])->toBe(1);
    expect($payload['inquiries']['closed'])->toBe(1);
    expect($payload['inquiries']['unread'])->toBe(1);
});

it('matches admin list totals and performs no writes', function () {
    $token = aoAdminToken();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();

    for ($i = 0; $i < 12; $i++) {
        aoThread($i % 2 === 0 ? $farmer : $buyer);
    }
    aoThread($farmer, ['hidden_at' => now()]);

    aoIssue($buyer);
    aoIssue($farmer, ['status' => IssueStatus::IN_PROGRESS->value]);
    aoReport($buyer, ReportTargetType::THREAD, 424001);
    $failedInbox = aoMessage(['status' => ContactStatus::UNREAD->value]);
    aoMessageReply($failedInbox, ['delivery_status' => ReplyDeliveryStatus::FAILED->value]);

    $logCount = AdminActionLog::count();

    $first = $this->withToken($token)->getJson('/api/v1/admin/overview');
    $first->assertOk();
    $overview = $first->json('data');

    expect(AdminActionLog::count())->toBe($logCount);

    $second = $this->withToken($token)->getJson('/api/v1/admin/overview')->json('data');
    unset($overview['generated_at'], $second['generated_at']);
    expect($second)->toBe($overview);
    expect(AdminActionLog::count())->toBe($logCount);

    $headers = ['Authorization' => 'Bearer '.$token];
    $farmerTotal = $this->getJson('/api/v1/admin/users?role=farmer', $headers)->json('meta.total');
    $buyerTotal = $this->getJson('/api/v1/admin/users?role=buyer', $headers)->json('meta.total');
    expect($farmerTotal + $buyerTotal)->toBe($overview['users']['members_total']);
    expect($this->getJson('/api/v1/admin/users?role=members', $headers)->json('meta.total'))
        ->toBe($overview['users']['members_total']);

    expect($this->getJson('/api/v1/admin/content/thread', $headers)->json('meta.total'))
        ->toBe($overview['content']['thread']['total']);
    expect($this->getJson('/api/v1/admin/issues?status=open', $headers)->json('meta.total'))
        ->toBe($overview['issues']['open']);
    expect($this->getJson('/api/v1/admin/reports?status=open', $headers)->json('meta.total'))
        ->toBe($overview['reports']['open']);
    expect($this->getJson('/api/v1/admin/contact-messages?status=unread', $headers)->json('meta.total'))
        ->toBe($overview['inquiries']['unread']);
    // failed_replies stays a reply-row count per spec ("failed outbound reply count").
    // List totals match in all UI-reachable states because retry reuses the same
    // row via a delivery_generation bump; direct-API multi-queue can still hold
    // 2 failed rows on 1 inquiry, in which case the filtered list counts affected
    // inquiries rather than reply rows.
    expect($this->getJson('/api/v1/admin/contact-messages?delivery=failed', $headers)->json('meta.total'))
        ->toBe($overview['inquiries']['failed_replies']);
});
