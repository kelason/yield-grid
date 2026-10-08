<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Marketplace\Services\PaymentGatewayInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\AdminActionLog;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $this->farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $this->recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
});

function makeListing(int $farmerId, ContractStatus $status): HarvestListing
{
    return HarvestListing::create([
        'farmer_id' => $farmerId,
        'title' => 'Fresh rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'status' => $status,
    ]);
}

function mktvisAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function mktvisContract(int $farmerId, int $recommendationId, array $overrides = []): ForwardContract
{
    return ForwardContract::factory()->available()->create(array_merge([
        'farmer_id' => $farmerId,
        'crop_recommendation_id' => $recommendationId,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ], $overrides));
}

function mktvisHide(User $admin, ReportTargetType $type, int $id, string $reason = 'suspected fraud'): mixed
{
    return app(ModerateMarketplaceContentAction::class)->execute($admin, $type, (string) $id, true, $reason);
}

function mktvisRestore(User $admin, ReportTargetType $type, int $id): mixed
{
    return app(ModerateMarketplaceContentAction::class)->execute($admin, $type, (string) $id, false, 'appeal upheld');
}

function mktvisWebhookPayload(string $checkoutId, string $eventType = 'checkout_session.payment.paid'): string
{
    return json_encode([
        'data' => [
            'attributes' => [
                'type' => $eventType,
                'data' => [
                    'id' => $checkoutId,
                    'attributes' => [
                        'payment_intent' => ['id' => 'pi_mktvis_123'],
                        'payment_method_used' => 'gcash',
                    ],
                ],
            ],
        ],
    ]);
}

function mktvisPostSignedWebhook(string $payload): TestResponse
{
    $timestamp = time();
    $signature = 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$payload, 'test-secret');

    return test()->call(
        'POST',
        '/api/v1/webhooks/paymongo',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => $signature],
        $payload
    );
}

it('shows an available listing to guests', function () {
    $listing = makeListing($this->farmer->id, ContractStatus::AVAILABLE);

    $this->getJson("/api/v1/market/items/listing/{$listing->id}")->assertOk();
});

it('hides a sold listing from public item detail', function () {
    $listing = makeListing($this->farmer->id, ContractStatus::SOLD);

    $this->getJson("/api/v1/market/items/listing/{$listing->id}")->assertNotFound();
});

it('hides a sold contract from public item detail', function () {
    $contract = ForwardContract::factory()->sold()->create([
        'farmer_id' => $this->farmer->id,
        'crop_recommendation_id' => $this->recommendation->id,
    ]);

    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();
});

it('suppresses a hidden contract from the public catalog and detail', function () {
    $admin = mktvisAdmin();
    $visible = mktvisContract($this->farmer->id, $this->recommendation->id);
    $hidden = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $hidden->id);

    $ids = collect($this->getJson('/api/v1/market/contracts')->json('data'))
        ->where('type', 'contract')->pluck('id')->all();

    expect($ids)->toContain($visible->id)->not->toContain($hidden->id);

    $this->getJson("/api/v1/market/items/contract/{$hidden->id}")->assertNotFound();
    $this->getJson("/api/v1/market/items/contract/{$visible->id}")->assertOk();
});

it('suppresses a hidden listing from the public catalog and detail', function () {
    $admin = mktvisAdmin();
    $visible = makeListing($this->farmer->id, ContractStatus::AVAILABLE);
    $hidden = makeListing($this->farmer->id, ContractStatus::AVAILABLE);

    mktvisHide($admin, ReportTargetType::LISTING, $hidden->id);

    $ids = collect($this->getJson('/api/v1/market/contracts')->json('data'))
        ->where('type', 'listing')->pluck('id')->all();

    expect($ids)->toContain($visible->id)->not->toContain($hidden->id);

    $this->getJson("/api/v1/market/items/listing/{$hidden->id}")->assertNotFound();
    $this->getJson("/api/v1/market/items/listing/{$visible->id}")->assertOk();
});

it('keeps hidden items visible to the owner with is_hidden and no purchasability', function () {
    $admin = mktvisAdmin();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);
    $listing = makeListing($this->farmer->id, ContractStatus::AVAILABLE);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);
    mktvisHide($admin, ReportTargetType::LISTING, $listing->id);

    $list = $this->actingAs($this->farmer)->getJson('/api/v1/farmer/contracts')->assertOk()->json('data');
    $listedContract = collect($list)->firstWhere('id', $contract->id);
    $listedListing = collect($list)->firstWhere('id', $listing->id);

    expect($listedContract['is_hidden'])->toBeTrue()
        ->and($listedContract['is_purchasable'])->toBeFalse()
        ->and($listedListing['is_hidden'])->toBeTrue()
        ->and($listedListing['is_purchasable'])->toBeFalse();

    $this->actingAs($this->farmer)->getJson("/api/v1/farmer/contracts/{$contract->id}")
        ->assertOk()
        ->assertJsonPath('data.is_hidden', true)
        ->assertJsonPath('data.is_purchasable', false);
});

it('never changes financial columns when hiding or restoring', function () {
    $admin = mktvisAdmin();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);
    $before = $contract->only(['quantity_kg', 'price_per_kg', 'total_price', 'currency', 'status']);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);
    expect($contract->fresh()->only(['quantity_kg', 'price_per_kg', 'total_price', 'currency', 'status']))
        ->toEqual($before);

    mktvisRestore($admin, ReportTargetType::CONTRACT, $contract->id);
    expect($contract->fresh()->only(['quantity_kg', 'price_per_kg', 'total_price', 'currency', 'status']))
        ->toEqual($before);

    $actions = AdminActionLog::where('subject_type', 'contract')->where('subject_id', (string) $contract->id)
        ->orderBy('id')->get()->map(fn (AdminActionLog $log) => $log->action)->all();
    expect($actions)->toEqual([AdminAction::CONTENT_HIDDEN, AdminAction::CONTENT_RESTORED]);
});

it('keeps owner and buyer purchase history intact after hiding', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    $purchaseId = $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 100,
        'payment_option' => 'cash',
    ])->assertOk()->json('purchase_id');

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    $this->actingAs($buyer)->getJson('/api/v1/buyer/purchases')->assertOk()
        ->assertJsonPath('data.0.id', $purchaseId);
    $this->actingAs($buyer)->getJson("/api/v1/buyer/purchases/{$purchaseId}")->assertOk()
        ->assertJsonPath('data.contract.is_hidden', true);
    $this->actingAs($this->farmer)->getJson('/api/v1/farmer/purchases')->assertOk()
        ->assertJsonPath('data.0.id', $purchaseId);

    $purchase = Purchase::findOrFail($purchaseId);
    expect($purchase->payment_status)->toBe(PaymentStatus::PENDING)
        ->and((float) $purchase->amount_paid)->toBe(300.0)
        ->and((float) $purchase->total_contract_amount)->toBe(3000.0);
});

it('rejects cash checkout on a hidden contract with no side effects', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 10,
        'payment_option' => 'cash',
    ])->assertStatus(409);

    expect(Purchase::count())->toBe(0);
    expect($contract->fresh()->only(['quantity_kg', 'total_price', 'status']))
        ->toEqual($contract->only(['quantity_kg', 'total_price', 'status']));
});

it('rejects online checkout on a hidden listing without calling the gateway', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $listing = makeListing($this->farmer->id, ContractStatus::AVAILABLE);

    mktvisHide($admin, ReportTargetType::LISTING, $listing->id);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->never();
    $this->app->instance(PayMongoService::class, $mock);

    $this->actingAs($buyer)->postJson("/api/v1/market/listing/{$listing->id}/checkout", [
        'quantity_kg' => 10,
        'payment_option' => 'paymongo',
    ])->assertStatus(409);

    expect(Purchase::count())->toBe(0);
    expect((float) $listing->fresh()->quantity_kg)->toBe(100.0);
    expect($listing->fresh()->status)->toBe(ContractStatus::AVAILABLE);
});

it('completes an existing online purchase normally after the root is hidden', function () {
    config(['services.paymongo.webhook_secret' => 'test-secret']);
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->once()->andReturn([
        'checkout_url' => 'https://paymongo.test/checkout/mktvis',
        'checkout_id' => 'cs_mktvis_root',
    ]);
    $this->app->instance(PayMongoService::class, $mock);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 40,
        'payment_option' => 'paymongo',
    ])->assertOk();

    $purchase = Purchase::firstOrFail();
    $clone = ForwardContract::findOrFail($purchase->forward_contract_id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    $this->app->forgetInstance(PayMongoService::class);

    mktvisPostSignedWebhook(mktvisWebhookPayload('cs_mktvis_root'))->assertOk();

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect($clone->fresh()->status)->toBe(ContractStatus::SOLD);
    expect($contract->fresh()->hidden_at)->not->toBeNull();
    expect((float) $contract->fresh()->quantity_kg)->toBe(60.0);
});

it('reconciles an existing purchase normally after the root is hidden', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->once()->andReturn([
        'checkout_url' => 'https://paymongo.test/checkout/mktvis3',
        'checkout_id' => 'cs_mktvis_verify',
    ]);
    $this->app->instance(PayMongoService::class, $mock);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 100,
        'payment_option' => 'paymongo',
    ])->assertOk();

    $purchase = Purchase::firstOrFail();

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('getCheckoutSession')->once()->with('cs_mktvis_verify')->andReturn([
        'data' => ['attributes' => [
            'payments' => [['id' => 'pay_1', 'attributes' => ['status' => 'paid', 'source' => ['type' => 'gcash']]]],
        ]],
    ]);
    $this->app->instance(PaymentGatewayInterface::class, $gateway);

    $this->actingAs($buyer)->getJson("/api/v1/checkout/{$purchase->id}/verify")
        ->assertOk()
        ->assertJsonPath('status', PaymentStatus::COMPLETED->value);

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect($contract->fresh()->status)->toBe(ContractStatus::PARTIALLY_PAID);
    expect($contract->fresh()->hidden_at)->not->toBeNull();
    expect((float) $contract->fresh()->quantity_kg)->toBe(100.0);
});

it('never republishes a root-hidden clone after cancellation', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->once()->andReturn([
        'checkout_url' => 'https://paymongo.test/checkout/mktvis2',
        'checkout_id' => 'cs_mktvis_cancel',
    ]);
    $this->app->instance(PayMongoService::class, $mock);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 40,
        'payment_option' => 'paymongo',
    ])->assertOk();

    $purchase = Purchase::firstOrFail();
    $cloneId = (int) $purchase->forward_contract_id;

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    $this->actingAs($buyer)->postJson("/api/v1/checkout/{$purchase->id}/cancel")->assertOk();

    expect(ForwardContract::findOrFail($cloneId)->status)->toBe(ContractStatus::AVAILABLE);

    $this->getJson("/api/v1/market/items/contract/{$cloneId}")->assertNotFound();
    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$cloneId}/checkout", [
        'quantity_kg' => 5,
        'payment_option' => 'cash',
    ])->assertStatus(409);
});

it('normalizes a clone hide to its moderation root', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 40,
        'payment_option' => 'cash',
    ])->assertOk();

    $cloneId = (int) Purchase::firstOrFail()->forward_contract_id;
    expect($cloneId)->not->toBe($contract->id);

    $root = mktvisHide($admin, ReportTargetType::CONTRACT, $cloneId);

    expect((int) $root->id)->toBe($contract->id);
    expect($contract->fresh()->hidden_at)->not->toBeNull();

    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();
    $this->getJson("/api/v1/market/items/contract/{$cloneId}")->assertNotFound();
});

it('restores a hidden root back to purchasable without touching money', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);
    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();

    mktvisRestore($admin, ReportTargetType::CONTRACT, $contract->id);

    $this->getJson("/api/v1/market/items/contract/{$contract->id}")
        ->assertOk()
        ->assertJsonPath('data.is_hidden', false)
        ->assertJsonPath('data.is_purchasable', true);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 10,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects repeated hide and restore transitions', function () {
    $admin = mktvisAdmin();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id);

    expect(fn () => mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id))
        ->toThrow(LogicException::class);

    mktvisRestore($admin, ReportTargetType::CONTRACT, $contract->id);

    expect(fn () => mktvisRestore($admin, ReportTargetType::CONTRACT, $contract->id))
        ->toThrow(LogicException::class);
});

it('rejects non-marketplace types in the marketplace moderation action', function () {
    $admin = mktvisAdmin();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    expect(fn () => app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::THREAD, (string) $contract->id, true, 'wrong action'
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::CONTRACT, (string) $contract->id, true, '   '
    ))->toThrow(InvalidArgumentException::class);
});

it('never exposes moderation notes in member responses', function () {
    $admin = mktvisAdmin();
    $contract = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $contract->id, 'internal fraud suspicion');

    $detail = $this->actingAs($this->farmer)->getJson("/api/v1/farmer/contracts/{$contract->id}")->assertOk();
    $list = $this->actingAs($this->farmer)->getJson('/api/v1/farmer/contracts')->assertOk();

    foreach ([$detail->json(), $list->json()] as $payload) {
        expect(json_encode($payload))->not->toContain('hidden_reason')
            ->and(json_encode($payload))->not->toContain('hidden_by')
            ->and(json_encode($payload))->not->toContain('internal fraud suspicion');
    }
});

it('blocks reporting a hidden contract while visible ones stay reportable', function () {
    $admin = mktvisAdmin();
    $buyer = User::factory()->buyer()->create();
    $visible = mktvisContract($this->farmer->id, $this->recommendation->id);
    $hidden = mktvisContract($this->farmer->id, $this->recommendation->id);

    mktvisHide($admin, ReportTargetType::CONTRACT, $hidden->id);

    $this->actingAs($buyer)->postJson('/api/v1/reports', [
        'reportable_type' => 'contract',
        'reportable_id' => $hidden->id,
        'reason' => 'spam',
    ])->assertNotFound();

    $this->actingAs($buyer)->postJson('/api/v1/reports', [
        'reportable_type' => 'contract',
        'reportable_id' => $visible->id,
        'reason' => 'spam',
    ])->assertCreated();
});

it('paginates contracts for admin with visibility and search filters', function () {
    $admin = mktvisAdmin();
    $visible = mktvisContract($this->farmer->id, $this->recommendation->id, ['title' => 'Visible calamansi deal']);
    $hidden = mktvisContract($this->farmer->id, $this->recommendation->id, ['title' => 'Hidden mango deal']);

    mktvisHide($admin, ReportTargetType::CONTRACT, $hidden->id);

    $repository = app(ForwardContractRepositoryInterface::class);

    expect($repository->paginateForAdmin([], 15)->total())->toBe(2);
    expect($repository->paginateForAdmin(['visibility' => 'visible'], 15)->total())->toBe(1);
    expect($repository->paginateForAdmin(['visibility' => 'hidden'], 15)->total())->toBe(1);
    expect($repository->paginateForAdmin(['search' => 'mango'], 15)->total())->toBe(1);
    expect($repository->paginateForAdmin(['search' => 'calamansi', 'visibility' => 'visible'], 15)->total())->toBe(1);
    expect($repository->findById($visible->id)->id)->toBe($visible->id);
});
