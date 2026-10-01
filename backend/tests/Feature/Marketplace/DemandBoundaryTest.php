<?php

use App\Constants\MarketplaceConstants;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
    $this->buyer = User::factory()->buyer()->create();
    $this->address = Helper::makeAddress($this->buyer);
    $this->payload = [
        'title' => '600kg fresh tomatoes',
        'description' => 'For weekend market',
        'crop_name' => 'Tomato',
        'quantity_kg' => 600,
        'target_price_per_kg' => 45,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $this->address->id,
    ];
});

it('accepts a demand title at the max length', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::DEMAND_TITLE_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a demand title one character over the max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::DEMAND_TITLE_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('accepts a demand description at the max length', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::DEMAND_DESCRIPTION_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a demand description one character over the max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::DEMAND_DESCRIPTION_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});

it('accepts a demand crop name at the max length', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'crop_name' => str_repeat('a', MarketplaceConstants::DEMAND_CROP_NAME_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a demand crop name one character over the max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'crop_name' => str_repeat('a', MarketplaceConstants::DEMAND_CROP_NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['crop_name']);
});

it('accepts demand quantity at the min and max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::DEMAND_QUANTITY_MIN_KG,
    ]))->assertCreated();

    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'title' => 'Second demand',
        'quantity_kg' => MarketplaceConstants::DEMAND_QUANTITY_MAX_KG,
        'target_price_per_kg' => 1,
    ]))->assertCreated();
});

it('rejects demand quantity outside the min and max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'quantity_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);

    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::DEMAND_QUANTITY_MAX_KG + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts demand target price at the min and max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'target_price_per_kg' => MarketplaceConstants::DEMAND_PRICE_MIN,
    ]))->assertCreated();

    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'title' => 'Second demand',
        'quantity_kg' => 1,
        'target_price_per_kg' => MarketplaceConstants::DEMAND_PRICE_MAX,
    ]))->assertCreated();
});

it('rejects demand target price outside the min and max', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'target_price_per_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['target_price_per_kg']);

    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'target_price_per_kg' => MarketplaceConstants::DEMAND_PRICE_MAX + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['target_price_per_kg']);
});

it('rejects a demand whose total exceeds the order maximum', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'quantity_kg' => 1000000,
        'target_price_per_kg' => 10000,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts a demand whose total sits just under the order maximum', function () {
    $this->actingAs($this->buyer)->postJson('/api/v1/buyer/demands', array_merge($this->payload, [
        'quantity_kg' => 1000000,
        'target_price_per_kg' => 9999.99,
    ]))->assertCreated();
});
