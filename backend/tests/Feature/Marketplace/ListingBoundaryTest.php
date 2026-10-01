<?php

use App\Constants\MarketplaceConstants;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->farmer = User::factory()->farmer()->create();
    $this->payload = [
        'title' => 'Manual Rice',
        'description' => 'Fresh harvest',
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 45.5,
        'estimated_harvest_date' => now()->addDays(20)->format('Y-m-d'),
        'shelf_life_days' => 180,
        'is_harvest_available' => false,
    ];
});

it('accepts a listing title at the max length', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::LISTING_TITLE_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a listing title one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::LISTING_TITLE_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('accepts a listing description at the max length', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::LISTING_DESCRIPTION_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a listing description one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::LISTING_DESCRIPTION_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});

it('accepts a listing crop name at the max length', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'crop_name' => str_repeat('a', MarketplaceConstants::LISTING_CROP_NAME_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a listing crop name one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'crop_name' => str_repeat('a', MarketplaceConstants::LISTING_CROP_NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['crop_name']);
});

it('accepts listing quantity at the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Min qty listing',
        'quantity_kg' => MarketplaceConstants::LISTING_QUANTITY_MIN_KG,
    ]))->assertCreated();

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Max qty listing',
        'quantity_kg' => MarketplaceConstants::LISTING_QUANTITY_MAX_KG,
        'price_per_kg' => 0.01,
    ]))->assertCreated();
});

it('rejects listing quantity outside the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'quantity_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::LISTING_QUANTITY_MAX_KG + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts listing price at the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Min price listing',
        'price_per_kg' => MarketplaceConstants::LISTING_PRICE_MIN,
    ]))->assertCreated();

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Max price listing',
        'quantity_kg' => 1,
        'price_per_kg' => MarketplaceConstants::LISTING_PRICE_MAX,
    ]))->assertCreated();
});

it('rejects listing price outside the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'price_per_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'price_per_kg' => MarketplaceConstants::LISTING_PRICE_MAX + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);
});

it('accepts listing shelf life at the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Min shelf listing',
        'shelf_life_days' => MarketplaceConstants::LISTING_SHELF_LIFE_MIN_DAYS,
    ]))->assertCreated();

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'title' => 'Max shelf listing',
        'shelf_life_days' => MarketplaceConstants::LISTING_SHELF_LIFE_MAX_DAYS,
    ]))->assertCreated();
});

it('rejects listing shelf life outside the min and max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'shelf_life_days' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['shelf_life_days']);

    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'shelf_life_days' => MarketplaceConstants::LISTING_SHELF_LIFE_MAX_DAYS + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['shelf_life_days']);
});

it('rejects a listing whose total exceeds the order maximum', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'quantity_kg' => 999999,
        'price_per_kg' => 99999999,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts a listing whose total sits just under the order maximum', function () {
    // 999,999 x 9,999 = 9,989,990,001 sits under the cap.
    $this->actingAs($this->farmer)->postJson('/api/v1/farmer/listings', array_merge($this->payload, [
        'quantity_kg' => 999999,
        'price_per_kg' => 9999,
    ]))->assertCreated();
});
