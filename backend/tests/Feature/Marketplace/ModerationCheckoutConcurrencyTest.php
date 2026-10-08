<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Actions\CreateCashPurchaseAction;
use App\Domain\Marketplace\Actions\DecideDemandOfferAction;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Actions\SplitPurchasableAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use App\Marketplace\Controllers\PayMongoWebhookController;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const MCC_EXIT_CHECKOUT_WON = 10;
const MCC_EXIT_CHECKOUT_BLOCKED = 11;
const MCC_EXIT_HIDE_DONE = 20;
const MCC_EXIT_ACCEPT_WON = 12;
const MCC_EXIT_ACCEPT_BLOCKED = 13;
const MCC_EXIT_WEBHOOK_PROCESSED = 30;
const MCC_EXIT_WEBHOOK_DUPLICATE = 31;

function mccAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function mccRecommendation(int $farmerId): CropRecommendation
{
    $farm = Farm::create(['user_id' => $farmerId, 'name' => 'Race Farm']);

    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Race Plot',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10,
    ]);

    return CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
}

/**
 * @return array{0: mixed, 1: mixed}
 */
function mccSocketPair(): array
{
    $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($pair === false) {
        throw new RuntimeException('Could not create a barrier socket pair.');
    }

    return $pair;
}

function mccReadByte(mixed $stream): string
{
    $byte = fread($stream, 1);

    if ($byte === false || $byte === '') {
        throw new RuntimeException('Barrier channel closed unexpectedly.');
    }

    return $byte;
}

/**
 * Fork one race child. The child reconnects with a fresh PostgreSQL
 * connection, signals readiness, waits for the shared release byte, runs the
 * operation, and exits with the operation result code. Unexpected throwables
 * (including deadlocks) exit 1 so the parent can fail loudly.
 *
 * @param  array<int, mixed>  $closeInChild
 */
function mccForkChild(mixed $childEnd, array $closeInChild, Closure $operation): int
{
    $pid = pcntl_fork();

    if ($pid === -1) {
        throw new RuntimeException('Could not fork a race process.');
    }

    if ($pid === 0) {
        try {
            foreach ($closeInChild as $stream) {
                fclose($stream);
            }

            DB::purge();
            DB::purge('pgsql_race');

            fwrite($childEnd, 'R');
            mccReadByte($childEnd);

            $code = $operation();

            fclose($childEnd);
            exit($code);
        } catch (Throwable $e) {
            fwrite(STDERR, 'race child failed: '.get_class($e).': '.$e->getMessage());
            exit(1);
        }
    }

    return $pid;
}

/**
 * Release two forked children through the same barrier and collect results.
 *
 * @return array{int, int} exit codes [$first, $second]
 */
function mccReleaseAndWait(mixed $parentA, mixed $parentB, int $pidA, int $pidB): array
{
    mccReadByte($parentA);
    mccReadByte($parentB);

    fwrite($parentA, 'G');
    fwrite($parentB, 'G');

    pcntl_waitpid($pidA, $statusA);
    pcntl_waitpid($pidB, $statusB);

    fclose($parentA);
    fclose($parentB);

    return [pcntl_wexitstatus($statusA), pcntl_wexitstatus($statusB)];
}

function mccCleanup(
    array $userIds = [],
    array $contractIds = [],
    array $listingIds = [],
    array $demandIds = [],
    array $offerIds = [],
    array $purchaseIds = [],
): void {
    DB::table('admin_action_logs')->whereIn('subject_id', array_map(strval(...), array_merge($contractIds, $listingIds, $demandIds)))->delete();
    DB::table('purchases')->whereIn('id', $purchaseIds)->delete();
    DB::table('crop_demand_offers')->whereIn('id', $offerIds)->delete();
    DB::table('crop_demands')->whereIn('id', $demandIds)->delete();
    DB::table('forward_contracts')->whereIn('id', $contractIds)->delete();
    DB::table('harvest_listings')->whereIn('id', $listingIds)->delete();
    DB::table('user_addresses')->whereIn('user_id', $userIds)->delete();
    DB::table('crop_recommendations')->whereIn('plot_id', function ($query) use ($userIds): void {
        $query->select('plots.id')->from('plots')
            ->join('farms', 'farms.id', '=', 'plots.farm_id')
            ->whereIn('farms.user_id', $userIds);
    })->delete();
    DB::table('plots')->whereIn('farm_id', function ($query) use ($userIds): void {
        $query->select('id')->from('farms')->whereIn('user_id', $userIds);
    })->delete();
    DB::table('farms')->whereIn('user_id', $userIds)->delete();
    DB::table('users')->whereIn('id', $userIds)->delete();

    DB::purge('pgsql_race');
    DB::purge();

    if (DB::transactionLevel() === 0) {
        DB::beginTransaction();
    }
}

it('races a full cash checkout against a root hide without deadlock or quantity drift', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the race test.');
    }

    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $recommendation = mccRecommendation($farmer->id);
    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ]);

    $contractId = $contract->id;
    $buyerId = $buyer->id;
    $adminId = $admin->id;
    $userIds = [$admin->id, $farmer->id, $buyer->id];

    DB::commit();

    try {
        [$parentA, $childA] = mccSocketPair();
        [$parentB, $childB] = mccSocketPair();

        $pidA = mccForkChild($childA, [$parentA, $parentB, $childB], function () use ($contractId, $buyerId): int {
            try {
                app(CreateCashPurchaseAction::class)->execute(
                    ForwardContract::findOrFail($contractId), $buyerId, 100.0
                );

                return MCC_EXIT_CHECKOUT_WON;
            } catch (LogicException) {
                return MCC_EXIT_CHECKOUT_BLOCKED;
            }
        });

        $pidB = mccForkChild($childB, [$parentA, $parentB, $childA], function () use ($contractId, $adminId): int {
            app(ModerateMarketplaceContentAction::class)->execute(
                User::findOrFail($adminId), ReportTargetType::CONTRACT, (string) $contractId, true, 'race hide'
            );

            return MCC_EXIT_HIDE_DONE;
        });

        [$checkoutCode, $hideCode] = mccReleaseAndWait($parentA, $parentB, $pidA, $pidB);

        expect($hideCode)->toBe(MCC_EXIT_HIDE_DONE);
        expect($checkoutCode)->toBeIn([MCC_EXIT_CHECKOUT_WON, MCC_EXIT_CHECKOUT_BLOCKED]);

        $fresh = ForwardContract::findOrFail($contractId);
        expect($fresh->hidden_at)->not->toBeNull();

        $purchaseIds = Purchase::where('buyer_id', $buyerId)->pluck('id')->all();

        if ($checkoutCode === MCC_EXIT_CHECKOUT_WON) {
            expect($purchaseIds)->toHaveCount(1);
            expect($fresh->status)->toBe(ContractStatus::RESERVED);
            expect((float) $fresh->quantity_kg)->toBe(100.0);
        } else {
            expect($purchaseIds)->toHaveCount(0);
            expect($fresh->status)->toBe(ContractStatus::AVAILABLE);
            expect((float) $fresh->quantity_kg)->toBe(100.0);
        }

        mccCleanup($userIds, [$contractId], [], [], [], $purchaseIds);
    } catch (Throwable $e) {
        mccCleanup($userIds, [$contractId]);
        throw $e;
    }
});

it('races a partial listing reservation against a hide and preserves lineage', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the race test.');
    }

    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $listing = HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Race rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'status' => ContractStatus::AVAILABLE,
    ]);

    $listingId = $listing->id;
    $adminId = $admin->id;
    $userIds = [$admin->id, $farmer->id];

    DB::commit();

    try {
        [$parentA, $childA] = mccSocketPair();
        [$parentB, $childB] = mccSocketPair();

        $pidA = mccForkChild($childA, [$parentA, $parentB, $childB], function () use ($listingId): int {
            try {
                DB::transaction(function () use ($listingId): void {
                    $split = app(SplitPurchasableAction::class);
                    $inner = $split->lockVisible(HarvestListing::class, $listingId);

                    if (! $inner->is_purchasable || (float) $inner->quantity_kg < 40.0) {
                        throw new LogicException('Item is no longer available or quantity insufficient.');
                    }

                    $item = $split->execute($inner, 40.0);
                    $item->status = ContractStatus::RESERVED;
                    $item->save();
                });

                return MCC_EXIT_CHECKOUT_WON;
            } catch (LogicException) {
                return MCC_EXIT_CHECKOUT_BLOCKED;
            }
        });

        $pidB = mccForkChild($childB, [$parentA, $parentB, $childA], function () use ($listingId, $adminId): int {
            app(ModerateMarketplaceContentAction::class)->execute(
                User::findOrFail($adminId), ReportTargetType::LISTING, (string) $listingId, true, 'race hide'
            );

            return MCC_EXIT_HIDE_DONE;
        });

        [$reserveCode, $hideCode] = mccReleaseAndWait($parentA, $parentB, $pidA, $pidB);

        expect($hideCode)->toBe(MCC_EXIT_HIDE_DONE);
        expect($reserveCode)->toBeIn([MCC_EXIT_CHECKOUT_WON, MCC_EXIT_CHECKOUT_BLOCKED]);

        $original = HarvestListing::findOrFail($listingId);
        expect($original->hidden_at)->not->toBeNull();

        $clones = HarvestListing::where('moderation_root_id', $listingId)->get();
        $allListingIds = HarvestListing::pluck('id')->all();

        if ($reserveCode === MCC_EXIT_CHECKOUT_WON) {
            expect($clones)->toHaveCount(1);

            $clone = $clones->first();
            expect($clone->status)->toBe(ContractStatus::RESERVED);
            expect((float) $clone->quantity_kg)->toBe(40.0);
            expect($clone->hidden_at)->toBeNull();
            expect($clone->hidden_by)->toBeNull();
            expect($clone->hidden_reason)->toBeNull();
            expect((float) $original->quantity_kg)->toBe(60.0);
            expect((float) $original->quantity_kg + (float) $clone->quantity_kg)->toBe(100.0);
        } else {
            expect($clones)->toHaveCount(0);
            expect((float) $original->quantity_kg)->toBe(100.0);
            expect($original->status)->toBe(ContractStatus::AVAILABLE);
        }

        mccCleanup($userIds, [], $allListingIds);
    } catch (Throwable $e) {
        mccCleanup($userIds, [], HarvestListing::pluck('id')->all());
        throw $e;
    }
});

it('serializes checkout behind a held root lock and rejects after hide commits', function () {
    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $recommendation = mccRecommendation($farmer->id);
    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ]);

    $contractId = $contract->id;
    $buyerId = $buyer->id;
    $userIds = [$admin->id, $farmer->id, $buyer->id];

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            $race->table('forward_contracts')->where('id', $contractId)->lockForUpdate()->first();
            $race->table('forward_contracts')->where('id', $contractId)->update(['hidden_at' => now()]);

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(CreateCashPurchaseAction::class)->execute(
                    ForwardContract::findOrFail($contractId), $buyerId, 100.0
                );
                $this->fail('Checkout slipped past the held root lock.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }

            expect(Purchase::count())->toBe(0);
        } finally {
            $race->rollBack();
        }

        app(ModerateMarketplaceContentAction::class)->execute(
            $admin->fresh(), ReportTargetType::CONTRACT, (string) $contractId, true, 'hide first'
        );

        try {
            app(CreateCashPurchaseAction::class)->execute(
                ForwardContract::findOrFail($contractId), $buyerId, 100.0
            );
            $this->fail('Checkout succeeded on a hidden root.');
        } catch (LogicException) {
            // Expected: hide wins, checkout is rejected.
        }

        expect(Purchase::count())->toBe(0);
        expect((float) ForwardContract::findOrFail($contractId)->quantity_kg)->toBe(100.0);
        expect(ForwardContract::findOrFail($contractId)->status)->toBe(ContractStatus::AVAILABLE);
    } finally {
        mccCleanup($userIds, [$contractId]);
    }
});

it('serializes hide behind held checkout locks and lets the purchase complete', function () {
    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $recommendation = mccRecommendation($farmer->id);
    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ]);

    $contractId = $contract->id;
    $buyerId = $buyer->id;
    $userIds = [$admin->id, $farmer->id, $buyer->id];

    $purchase = app(CreateCashPurchaseAction::class)->execute($contract, $buyerId, 40.0);
    $cloneId = (int) $purchase->forward_contract_id;

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            // Root-before-item, matching checkout and moderation lock order.
            $race->table('forward_contracts')->where('id', $contractId)->lockForUpdate()->first();
            $race->table('forward_contracts')->where('id', $cloneId)->lockForUpdate()->first();

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(ModerateMarketplaceContentAction::class)->execute(
                    $admin->fresh(), ReportTargetType::CONTRACT, (string) $cloneId, true, 'racing hide'
                );
                $this->fail('Hide slipped past the held checkout locks.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }

            expect(ForwardContract::findOrFail($contractId)->hidden_at)->toBeNull();
        } finally {
            $race->rollBack();
        }

        $root = app(ModerateMarketplaceContentAction::class)->execute(
            $admin->fresh(), ReportTargetType::CONTRACT, (string) $cloneId, true, 'racing hide'
        );

        expect((int) $root->id)->toBe($contractId);
        expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
        expect((float) ForwardContract::findOrFail($contractId)->quantity_kg)->toBe(60.0);
        expect((float) ForwardContract::findOrFail($cloneId)->quantity_kg)->toBe(40.0);
    } finally {
        mccCleanup($userIds, [$contractId, $cloneId], [], [], [], [$purchase->id]);
    }
});

it('races a demand hide against offer acceptance without deadlock or drift', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the race test.');
    }

    $admin = mccAdmin();
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['quantity_kg' => 150]);

    $demandId = $demand->id;
    $offerId = $offer->id;
    $adminId = $admin->id;
    $userIds = [$admin->id, $buyer->id, $farmer->id];

    DB::commit();

    try {
        [$parentA, $childA] = mccSocketPair();
        [$parentB, $childB] = mccSocketPair();

        $pidA = mccForkChild($childA, [$parentA, $parentB, $childB], function () use ($offerId): int {
            try {
                app(DecideDemandOfferAction::class)->accept(CropDemandOffer::findOrFail($offerId));

                return MCC_EXIT_ACCEPT_WON;
            } catch (LogicException) {
                return MCC_EXIT_ACCEPT_BLOCKED;
            }
        });

        $pidB = mccForkChild($childB, [$parentA, $parentB, $childA], function () use ($demandId, $adminId): int {
            app(ModerateMarketplaceContentAction::class)->execute(
                User::findOrFail($adminId), ReportTargetType::DEMAND, (string) $demandId, true, 'race hide'
            );

            return MCC_EXIT_HIDE_DONE;
        });

        [$acceptCode, $hideCode] = mccReleaseAndWait($parentA, $parentB, $pidA, $pidB);

        expect($hideCode)->toBe(MCC_EXIT_HIDE_DONE);
        expect($acceptCode)->toBeIn([MCC_EXIT_ACCEPT_WON, MCC_EXIT_ACCEPT_BLOCKED]);

        $freshDemand = CropDemand::findOrFail($demandId);
        $freshOffer = CropDemandOffer::findOrFail($offerId);

        expect($freshDemand->hidden_at)->not->toBeNull();

        if ($acceptCode === MCC_EXIT_ACCEPT_WON) {
            expect($freshOffer->status)->toBe(DemandOfferStatus::ACCEPTED);
            expect((float) $freshDemand->remaining_quantity_kg)->toBe(450.0);
        } else {
            expect($freshOffer->status)->toBe(DemandOfferStatus::PENDING);
            expect((float) $freshDemand->remaining_quantity_kg)->toBe(600.0);
        }
    } finally {
        mccCleanup($userIds, [], [], [$demandId], [$offerId]);
    }
});

it('processes duplicate paid webhooks concurrently without drift or flag loss', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the race test.');
    }

    config(['services.paymongo.webhook_secret' => 'test-secret']);

    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $recommendation = mccRecommendation($farmer->id);
    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ]);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->once()->andReturn([
        'checkout_url' => 'https://paymongo.test/checkout/mcc',
        'checkout_id' => 'cs_mcc_duplicate',
    ]);
    $this->app->instance(PayMongoService::class, $mock);

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 100,
        'payment_option' => 'paymongo',
    ])->assertOk();

    $purchase = Purchase::firstOrFail();

    app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::CONTRACT, (string) $contract->id, true, 'hidden before settle'
    );

    $payload = json_encode([
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_mcc_duplicate',
                    'attributes' => [
                        'payment_intent' => ['id' => 'pi_mcc_123'],
                        'payment_method_used' => 'gcash',
                    ],
                ],
            ],
        ],
    ]);
    $timestamp = time();
    $signature = 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$payload, 'test-secret');

    $purchaseId = $purchase->id;
    $contractId = $contract->id;
    $userIds = [$admin->id, $farmer->id, $buyer->id];

    DB::commit();

    try {
        [$parentA, $childA] = mccSocketPair();
        [$parentB, $childB] = mccSocketPair();

        $postWebhook = function () use ($payload, $signature): int {
            app()->forgetInstance(PayMongoService::class);

            $request = Request::create(
                '/api/v1/webhooks/paymongo', 'POST', [], [], [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => $signature],
                $payload
            );

            $body = app(PayMongoWebhookController::class)->handle($request)->getContent();

            return match ($body) {
                'OK' => MCC_EXIT_WEBHOOK_PROCESSED,
                'Already processed' => MCC_EXIT_WEBHOOK_DUPLICATE,
                default => throw new RuntimeException('Unexpected webhook response: '.$body),
            };
        };

        $pidA = mccForkChild($childA, [$parentA, $parentB, $childB], $postWebhook);
        $pidB = mccForkChild($childB, [$parentA, $parentB, $childA], $postWebhook);

        [$first, $second] = mccReleaseAndWait($parentA, $parentB, $pidA, $pidB);

        expect([$first, $second])->toContain(MCC_EXIT_WEBHOOK_PROCESSED);
        expect([$first, $second])->toContain(MCC_EXIT_WEBHOOK_DUPLICATE);

        expect(Purchase::findOrFail($purchaseId)->payment_status)->toBe(PaymentStatus::COMPLETED);
        expect(ForwardContract::findOrFail($contractId)->status)->toBe(ContractStatus::SOLD);
        expect(ForwardContract::findOrFail($contractId)->hidden_at)->not->toBeNull();
        expect((float) ForwardContract::findOrFail($contractId)->quantity_kg)->toBe(100.0);
    } finally {
        mccCleanup($userIds, [$contractId], [], [], [], [$purchaseId]);
    }
});

it('treats historical null roots as independent moderation selves', function () {
    $admin = mccAdmin();
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $recommendation = mccRecommendation($farmer->id);
    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'quantity_kg' => 100,
        'price_per_kg' => 30,
        'total_price' => 3000,
    ]);

    expect($contract->moderation_root_id)->toBeNull();

    $root = app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::CONTRACT, (string) $contract->id, true, 'historic row'
    );
    expect((int) $root->id)->toBe($contract->id);

    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();

    app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::CONTRACT, (string) $contract->id, false, 'appeal upheld'
    );

    $this->actingAs($buyer)->postJson("/api/v1/market/contract/{$contract->id}/checkout", [
        'quantity_kg' => 40,
        'payment_option' => 'cash',
    ])->assertOk();

    $clone = ForwardContract::findOrFail((int) Purchase::firstOrFail()->forward_contract_id);

    expect((int) $clone->moderation_root_id)->toBe($contract->id);
    expect($clone->hidden_at)->toBeNull();
    expect($clone->hidden_by)->toBeNull();
    expect($clone->hidden_reason)->toBeNull();

    app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::CONTRACT, (string) $contract->id, true, 'root hide covers clone'
    );

    expect($clone->fresh()->hidden_at)->toBeNull();
    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();
    $this->getJson("/api/v1/market/items/contract/{$clone->id}")->assertNotFound();
});
