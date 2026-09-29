<?php

use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
});

it('creates an address and promotes the first one to default', function () {
    $user = User::factory()->buyer()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'is_default' => false,
    ]));

    $response->assertCreated();
    $response->assertJsonPath('data.is_default', true);
    $response->assertJsonPath('data.barangay', 'Poblacion');
    $response->assertJsonPath('data.city_municipality', 'Makati');
    expect($response->json('data.maps_url'))->toContain('google.com/maps/search');

    $this->assertDatabaseHas('user_addresses', [
        'user_id' => $user->id,
        'barangay_code' => Helper::BARANGAY_POBLACION,
        'is_default' => true,
    ]);
});

it('moves the default flag when a new default is saved', function () {
    $user = User::factory()->buyer()->create();
    $first = Helper::makeAddress($user);

    $this->actingAs($user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'label' => 'Farm gate',
        'is_default' => true,
    ]))->assertCreated();

    expect($first->fresh()->is_default)->toBeFalse();
    expect(UserAddress::where('user_id', $user->id)->where('is_default', true)->count())->toBe(1);
});

it('refuses to delete an address used by a demand', function () {
    $buyer = User::factory()->buyer()->create();
    $address = Helper::makeAddress($buyer);
    Helper::makeDemand($buyer, $address);

    $this->actingAs($buyer)->deleteJson("/api/v1/user/addresses/{$address->id}")
        ->assertStatus(409);
});

it('reassigns default to the oldest address when the default is deleted', function () {
    $user = User::factory()->buyer()->create();
    $first = Helper::makeAddress($user);
    $second = Helper::makeAddress($user, ['label' => 'Second', 'is_default' => false]);

    $this->actingAs($user)->deleteJson("/api/v1/user/addresses/{$first->id}")->assertOk();

    expect($second->fresh()->is_default)->toBeTrue();
});

it('prevents users from touching addresses they do not own', function () {
    $owner = User::factory()->buyer()->create();
    $address = Helper::makeAddress($owner);
    $stranger = User::factory()->buyer()->create();

    $this->actingAs($stranger)->putJson("/api/v1/user/addresses/{$address->id}", Helper::addressPayload())
        ->assertForbidden();

    $this->actingAs($stranger)->deleteJson("/api/v1/user/addresses/{$address->id}")
        ->assertForbidden();
});

it('registers a user with an optional address', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Maria Buyer',
        'email' => 'maria@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'buyer',
        'address' => Helper::addressPayload(),
    ]);

    $response->assertCreated();

    $user = User::where('email', 'maria@example.com')->firstOrFail();
    expect($user->addresses()->count())->toBe(1);
    expect($user->defaultAddress()->barangay_code)->toBe(Helper::BARANGAY_POBLACION);
});

it('registers a user without an address', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jose Farmer',
        'email' => 'jose@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ]);

    $response->assertCreated();
    expect(User::where('email', 'jose@example.com')->firstOrFail()->addresses()->count())->toBe(0);
});

it('rejects an invalid PSGC combination', function () {
    $user = User::factory()->buyer()->create();

    $this->actingAs($user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'barangay_code' => '0000000000',
    ]))->assertStatus(422);
});

it('rejects a pin outside the selected area', function () {
    $user = User::factory()->buyer()->create();

    // Nominatim is faked at (14.55, 121.03); Davao is far outside the radius.
    $this->actingAs($user)->postJson('/api/v1/user/addresses', Helper::addressPayload([
        'latitude' => 7.19,
        'longitude' => 125.45,
    ]))->assertStatus(422);
});

it('exposes own addresses and location summary on profiles', function () {
    $user = User::factory()->buyer()->create();
    Helper::makeAddress($user);
    $stranger = User::factory()->farmer()->create();

    $own = $this->actingAs($user)->getJson("/api/v1/users/{$user->id}");
    $own->assertOk();
    $own->assertJsonPath('data.location_summary', 'Makati');
    expect($own->json('data.addresses'))->toHaveCount(1);

    $other = $this->actingAs($stranger)->getJson("/api/v1/users/{$user->id}");
    $other->assertOk();
    $other->assertJsonPath('data.location_summary', 'Makati');
    expect($other->json('data.addresses'))->toBeNull();
});

it('serves the PSGC cascade publicly', function () {
    $this->getJson('/api/v1/geo/regions')->assertOk()->assertJsonPath('data.0.code', Helper::REGION_NCR);

    $this->getJson('/api/v1/geo/provinces?region_code='.Helper::REGION_NCR)
        ->assertOk()
        ->assertJsonPath('data', []);

    $this->getJson('/api/v1/geo/cities-municipalities?region_code='.Helper::REGION_NCR)
        ->assertOk()
        ->assertJsonPath('data.0.code', Helper::CITY_MAKATI);

    $this->getJson('/api/v1/geo/barangays?city_municipality_code='.Helper::CITY_MAKATI)
        ->assertOk()
        ->assertJsonPath('data.0.code', Helper::BARANGAY_POBLACION);
});
