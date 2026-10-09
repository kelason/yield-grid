<?php

use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function verificationTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('verification-test-token')->plainTextToken;
}

function verificationAdminUser(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN]);
}

function verificationPolygonSql(string $geojson, int $plotId): void
{
    DB::update(
        'UPDATE plots SET polygon = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
        [$geojson, $plotId]
    );
}

it('denies guests with 401 on the verification queue and details', function () {
    $farm = Farm::factory()->create();
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    $this->getJson('/api/v1/admin/verifications')->assertUnauthorized();
    $this->getJson("/api/v1/admin/verifications/farms/{$farm->id}")->assertUnauthorized();
    $this->getJson("/api/v1/admin/verifications/plots/{$plot->id}")->assertUnauthorized();
});

it('denies farmers with 403 on the verification queue and details', function () {
    $farmer = User::factory()->farmer()->create();
    $token = verificationTokenFor($farmer);
    $farm = Farm::factory()->create();
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications')->assertForbidden();
    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/verifications/farms/{$farm->id}")->assertForbidden();
    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/verifications/plots/{$plot->id}")->assertForbidden();
});

it('denies unverified admins with 403 on the verification queue', function () {
    $admin = User::factory()->unverified()->create(['role' => UserRole::ADMIN]);
    $token = verificationTokenFor($admin);
    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications')->assertForbidden();
});

it('lists pending farms by default for verified admins', function () {
    $admin = verificationAdminUser();
    $token = verificationTokenFor($admin);
    Farm::factory()->create(['verification_status' => VerificationStatus::VERIFIED]);
    $pending = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'farm')
        ->assertJsonPath('data.0.id', $pending->id)
        ->assertJsonPath('data.0.verification_status', VerificationStatus::PENDING->value);
});

it('filters the queue by scope and status', function () {
    $admin = verificationAdminUser();
    $token = verificationTokenFor($admin);
    $farm = Farm::factory()->create();
    Plot::factory()->create(['farm_id' => $farm->id, 'verification_status' => VerificationStatus::VERIFIED]);
    Plot::factory()->create(['farm_id' => $farm->id]);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications?scope=plots&status=verified')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'plot');

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications?scope=plots&status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('rejects invalid scope and status filters with 422', function () {
    $admin = verificationAdminUser();
    $token = verificationTokenFor($admin);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications?scope=orchards')->assertUnprocessable();
    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/verifications?status=maybe')->assertUnprocessable();
});

it('shows farm detail with farmer and plots count', function () {
    $admin = verificationAdminUser();
    $token = verificationTokenFor($admin);
    $farm = Farm::factory()->create(['name' => 'Green Acres', 'city' => 'Springfield']);
    Plot::factory()->count(2)->create(['farm_id' => $farm->id]);

    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/verifications/farms/{$farm->id}")
        ->assertOk()
        ->assertJsonPath('data.type', 'farm')
        ->assertJsonPath('data.name', 'Green Acres')
        ->assertJsonPath('data.city', 'Springfield')
        ->assertJsonPath('data.plots_count', 2)
        ->assertJsonPath('data.farmer.id', $farm->user_id);
});

it('shows plot detail with farm and polygon geojson', function () {
    $admin = verificationAdminUser();
    $token = verificationTokenFor($admin);
    $farm = Farm::factory()->create(['name' => 'Home Farm']);
    $plot = Plot::factory()->create(['farm_id' => $farm->id, 'name' => 'North Field']);
    verificationPolygonSql('{"type":"Polygon","coordinates":[[[0,0],[0,1],[1,0],[0,0]]]}', $plot->id);

    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/verifications/plots/{$plot->id}")
        ->assertOk()
        ->assertJsonPath('data.type', 'plot')
        ->assertJsonPath('data.name', 'North Field')
        ->assertJsonPath('data.farm.id', $farm->id)
        ->assertJsonPath('data.farmer.id', $farm->user_id)
        ->assertJsonPath('data.geojson.geometry.type', 'Polygon');
});
