<?php

use App\Constants\AddressConstants;
use App\Constants\GeoConstants;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
    $this->user = User::factory()->buyer()->create();
});

it('accepts a label at the max length', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'label' => str_repeat('a', AddressConstants::LABEL_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a label one character over the max', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'label' => str_repeat('a', AddressConstants::LABEL_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['label']);
});

it('accepts a street at the max length', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'street' => str_repeat('a', AddressConstants::STREET_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a street one character over the max', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'street' => str_repeat('a', AddressConstants::STREET_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['street']);
});

it('rejects latitude and longitude outside the valid ranges', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'latitude' => GeoConstants::LATITUDE_MAX + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['latitude']);

    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'latitude' => GeoConstants::LATITUDE_MIN - 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['latitude']);

    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'longitude' => GeoConstants::LONGITUDE_MAX + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['longitude']);

    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'longitude' => GeoConstants::LONGITUDE_MIN - 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['longitude']);
});

it('rejects latitude without longitude and vice versa', function () {
    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'latitude' => 14.551,
        'longitude' => null,
    ]))->assertStatus(422)->assertJsonValidationErrors(['longitude']);

    $this->actingAs($this->user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'latitude' => null,
        'longitude' => 121.031,
    ]))->assertStatus(422)->assertJsonValidationErrors(['latitude']);
});
