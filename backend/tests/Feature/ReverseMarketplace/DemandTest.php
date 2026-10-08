<?php

use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Shared\Middleware\EnsureUserHasMarketplaceAddress;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
});

function dmdmodAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function dmdmodHide(User $admin, CropDemand $demand): void
{
    app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::DEMAND, (string) $demand->id, true, 'suspected fraud'
    );
}

it('allows a buyer with an address to post a demand', function () {
    $buyer = User::factory()->buyer()->create();
    $address = Helper::makeAddress($buyer);

    $response = $this->actingAs($buyer)->postJson('/api/v1/buyer/demands', [
        'title' => '600kg fresh tomatoes',
        'description' => 'For weekend market',
        'crop_name' => 'Tomato',
        'quantity_kg' => 600,
        'target_price_per_kg' => 45,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $address->id,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', DemandStatus::OPEN->value);
    expect((float) $response->json('data.remaining_quantity_kg'))->toBe(600.0);
    $response->assertJsonPath('data.delivery_address.formatted_address', $response->json('data.delivery_address.formatted_address'));

    $this->assertDatabaseHas('crop_demands', [
        'buyer_id' => $buyer->id,
        'address_id' => $address->id,
        'crop_name' => 'Tomato',
        'status' => DemandStatus::OPEN->value,
    ]);
});

it('blocks demand posting without a saved address', function () {
    $buyer = User::factory()->buyer()->create();

    $response = $this->actingAs($buyer)->postJson('/api/v1/buyer/demands', [
        'title' => '600kg fresh tomatoes',
        'crop_name' => 'Tomato',
        'quantity_kg' => 600,
        'target_price_per_kg' => 45,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error_code', EnsureUserHasMarketplaceAddress::ERROR_CODE);
});

it('prevents farmers from posting demands', function () {
    $farmer = User::factory()->farmer()->create();
    $address = Helper::makeAddress($farmer);

    $this->actingAs($farmer)->postJson('/api/v1/buyer/demands', [
        'title' => 'Sneaky demand',
        'crop_name' => 'Rice',
        'quantity_kg' => 10,
        'target_price_per_kg' => 50,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $address->id,
    ])->assertForbidden();
});

it('rejects a delivery address owned by another user', function () {
    $buyer = User::factory()->buyer()->create();
    Helper::makeAddress($buyer);
    $stranger = User::factory()->buyer()->create();
    $otherAddress = Helper::makeAddress($stranger);

    $this->actingAs($buyer)->postJson('/api/v1/buyer/demands', [
        'title' => '600kg fresh tomatoes',
        'crop_name' => 'Tomato',
        'quantity_kg' => 600,
        'target_price_per_kg' => 45,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $otherAddress->id,
    ])->assertStatus(422);
});

it('lists open demands publicly with filters', function () {
    $buyer = User::factory()->buyer()->create();
    Helper::makeDemand($buyer);
    Helper::makeDemand($buyer, null, ['crop_name' => 'Rice', 'status' => DemandStatus::CANCELLED]);

    $response = $this->getJson('/api/v1/market/demands?crop=tomato');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.crop_name'))->toBe('Tomato');
    expect($response->json('data.0.delivery_address'))->toBeNull();
    expect($response->json('data.0.location_summary'))->toContain('Makati');
});

it('shows full delivery address to the owning buyer only', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $other = User::factory()->farmer()->create();

    $this->getJson("/api/v1/market/demands/{$demand->id}")
        ->assertOk()
        ->assertJsonPath('data.delivery_address', null);

    $this->actingAs($other)->getJson("/api/v1/market/demands/{$demand->id}")
        ->assertOk()
        ->assertJsonPath('data.delivery_address', null);

    $this->actingAs($buyer)->getJson("/api/v1/market/demands/{$demand->id}")
        ->assertOk()
        ->assertJsonPath('data.delivery_address.id', $demand->address_id);
});

it('allows the buyer to cancel an open demand and cancels pending offers', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer);

    $response = $this->actingAs($buyer)->patchJson("/api/v1/buyer/demands/{$demand->id}/cancel");

    $response->assertOk();
    expect($demand->fresh()->status)->toBe(DemandStatus::CANCELLED);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::CANCELLED);
});

it('refuses to cancel a fulfilled demand', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, [
        'status' => DemandStatus::FULFILLED,
        'remaining_quantity_kg' => 0,
    ]);

    $this->actingAs($buyer)->patchJson("/api/v1/buyer/demands/{$demand->id}/cancel")
        ->assertStatus(409);
});

it('prevents buyers from cancelling demands they do not own', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $other = User::factory()->buyer()->create();

    $this->actingAs($other)->patchJson("/api/v1/buyer/demands/{$demand->id}/cancel")
        ->assertForbidden();
});

it('rejects a demand whose total budget exceeds the maximum order total', function () {
    $buyer = User::factory()->buyer()->create();
    $address = Helper::makeAddress($buyer);

    $this->actingAs($buyer)->postJson('/api/v1/buyer/demands', [
        'title' => 'Huge order',
        'crop_name' => 'Rice',
        'quantity_kg' => 1000000,
        'target_price_per_kg' => 1000000,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $address->id,
    ])->assertStatus(422)->assertJsonValidationErrors('quantity_kg');
});

it('filters the buyer demand list by status', function () {
    $buyer = User::factory()->buyer()->create();
    Helper::makeDemand($buyer, null, ['status' => DemandStatus::OPEN]);
    Helper::makeDemand($buyer, null, ['status' => DemandStatus::FULFILLED, 'remaining_quantity_kg' => 0]);

    $this->actingAs($buyer)->getJson('/api/v1/buyer/demands?status=open')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', DemandStatus::OPEN->value);

    $this->actingAs($buyer)->getJson('/api/v1/buyer/demands?status=fulfilled')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    // Unknown values are ignored, so all of the buyer's demands return.
    $this->actingAs($buyer)->getJson('/api/v1/buyer/demands?status=bogus')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('excludes a hidden demand from public discovery while the owner keeps access', function () {
    $admin = dmdmodAdmin();
    $buyer = User::factory()->buyer()->create();
    $visible = Helper::makeDemand($buyer);
    $hidden = Helper::makeDemand($buyer);
    $stranger = User::factory()->farmer()->create();

    dmdmodHide($admin, $hidden);

    $ids = collect($this->getJson('/api/v1/market/demands')->json('data'))->pluck('id')->all();
    expect($ids)->toContain($visible->id)->not->toContain($hidden->id);

    $this->getJson("/api/v1/market/demands/{$hidden->id}")->assertNotFound();
    $this->actingAs($stranger)->getJson("/api/v1/market/demands/{$hidden->id}")->assertNotFound();

    $this->actingAs($buyer)->getJson("/api/v1/market/demands/{$hidden->id}")
        ->assertOk()
        ->assertJsonPath('data.is_hidden', true);
    $this->actingAs($buyer)->getJson('/api/v1/buyer/demands')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($hidden->fresh()->status)->toBe(DemandStatus::OPEN);
    expect((float) $hidden->fresh()->remaining_quantity_kg)->toBe(600.0);
});

it('keeps hidden demand detail available to an existing offer party', function () {
    $admin = dmdmodAdmin();
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $pendingFarmer = User::factory()->farmer()->create();
    $acceptedFarmer = User::factory()->farmer()->create();
    Helper::makeOffer($demand, $pendingFarmer, ['status' => DemandOfferStatus::PENDING]);
    Helper::makeOffer($demand, $acceptedFarmer, ['status' => DemandOfferStatus::ACCEPTED]);

    dmdmodHide($admin, $demand);

    // Pending parties keep access but still do not see the delivery address.
    $this->actingAs($pendingFarmer)->getJson("/api/v1/market/demands/{$demand->id}")
        ->assertOk()
        ->assertJsonPath('data.is_hidden', true)
        ->assertJsonPath('data.delivery_address', null);

    // Accepted parties keep their address access.
    $acceptedView = $this->actingAs($acceptedFarmer)->getJson("/api/v1/market/demands/{$demand->id}")
        ->assertOk()
        ->assertJsonPath('data.is_hidden', true)
        ->json();
    expect($acceptedView['data']['delivery_address'])->not->toBeNull();

    // Moderation notes never leak to members.
    $payload = $this->actingAs($buyer)->getJson("/api/v1/market/demands/{$demand->id}")->assertOk()->json();
    expect(json_encode($payload))->not->toContain('hidden_reason')
        ->and(json_encode($payload))->not->toContain('hidden_by');
});

it('blocks reporting a hidden demand', function () {
    $admin = dmdmodAdmin();
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $reporter = User::factory()->farmer()->create();

    dmdmodHide($admin, $demand);

    $this->actingAs($reporter)->postJson('/api/v1/reports', [
        'reportable_type' => 'demand',
        'reportable_id' => $demand->id,
        'reason' => 'suspected_fraud',
    ])->assertNotFound();
});
