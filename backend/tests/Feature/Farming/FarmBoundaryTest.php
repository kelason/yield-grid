<?php

use App\Constants\FarmingConstants;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->farmer = User::factory()->farmer()->create();
    $this->payload = [
        'name' => 'Green Acres',
        'city' => 'Springfield',
        'total_area' => 150.5,
    ];
});

it('accepts a farm name at the max length', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'name' => str_repeat('a', FarmingConstants::FARM_NAME_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a farm name one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'name' => str_repeat('a', FarmingConstants::FARM_NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects a missing farm name', function () {
    $payload = $this->payload;
    unset($payload['name']);

    $this->actingAs($this->farmer)->postJson('/api/v1/farms', $payload)
        ->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('accepts address fields at their max lengths', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'address' => str_repeat('a', FarmingConstants::FARM_ADDRESS_MAX_LENGTH),
        'country' => str_repeat('b', FarmingConstants::FARM_COUNTRY_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects an address one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'address' => str_repeat('a', FarmingConstants::FARM_ADDRESS_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['address']);
});

it('rejects a zip code one character over the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'zip' => str_repeat('1', FarmingConstants::FARM_ZIP_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['zip']);
});

it('accepts total area at the min and max bounds', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'total_area' => FarmingConstants::TOTAL_AREA_MIN_HECTARES,
    ]))->assertCreated();

    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'name' => 'Big Ranch',
        'total_area' => FarmingConstants::TOTAL_AREA_MAX_HECTARES,
    ]))->assertCreated();
});

it('rejects total area below the min and above the max', function () {
    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'total_area' => FarmingConstants::TOTAL_AREA_MIN_HECTARES - 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['total_area']);

    $this->actingAs($this->farmer)->postJson('/api/v1/farms', array_merge($this->payload, [
        'total_area' => FarmingConstants::TOTAL_AREA_MAX_HECTARES + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['total_area']);
});
