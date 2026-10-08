<?php

use App\Domain\Community\Models\ForumCategory;
use App\Policies\AdminUserPolicy;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function adminAccessTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('access-test-token')->plainTextToken;
}

function adminAccessEndpoints(int $knownId): array
{
    $missingId = 999999;

    return [
        'index' => ['GET', '/api/v1/admin/users'],
        'show known' => ['GET', "/api/v1/admin/users/{$knownId}"],
        'show missing' => ['GET', "/api/v1/admin/users/{$missingId}"],
        'suspend known' => ['POST', "/api/v1/admin/users/{$knownId}/suspend"],
        'suspend missing' => ['POST', "/api/v1/admin/users/{$missingId}/suspend"],
        'unsuspend known' => ['POST', "/api/v1/admin/users/{$knownId}/unsuspend"],
        'unsuspend missing' => ['POST', "/api/v1/admin/users/{$missingId}/unsuspend"],
    ];
}

it('denies guests with 401 on every admin endpoint for known and missing ids', function () {
    $member = User::factory()->farmer()->create();

    foreach (adminAccessEndpoints($member->id) as $label => [$method, $uri]) {
        $payload = str_starts_with($uri, '/api/v1/admin/users/') && $method === 'POST'
            ? ['reason' => 'probe']
            : [];

        $response = $method === 'GET'
            ? $this->getJson($uri)
            : $this->postJson($uri, $payload);

        $response->assertUnauthorized();
    }
});

it('denies farmers with 403 on every admin endpoint for known and missing ids', function () {
    $farmer = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($farmer);

    foreach (adminAccessEndpoints($farmer->id) as [$method, $uri]) {
        Auth::forgetGuards();

        $response = $method === 'GET'
            ? $this->withToken($token)->getJson($uri)
            : $this->withToken($token)->postJson($uri, ['reason' => 'probe']);

        $response->assertForbidden();
    }
});

it('denies buyers with 403 on every admin endpoint for known and missing ids', function () {
    $buyer = User::factory()->buyer()->create();
    $token = adminAccessTokenFor($buyer);

    foreach (adminAccessEndpoints($buyer->id) as [$method, $uri]) {
        Auth::forgetGuards();

        $response = $method === 'GET'
            ? $this->withToken($token)->getJson($uri)
            : $this->withToken($token)->postJson($uri, ['reason' => 'probe']);

        $response->assertForbidden();
    }
});

it('denies unverified admins with 403 on every admin endpoint for known and missing ids', function () {
    $admin = User::factory()->unverified()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($admin);

    foreach (adminAccessEndpoints($member->id) as [$method, $uri]) {
        Auth::forgetGuards();

        $response = $method === 'GET'
            ? $this->withToken($token)->getJson($uri)
            : $this->withToken($token)->postJson($uri, ['reason' => 'probe']);

        $response->assertForbidden();
    }
});

it('allows verified admins and returns 404 for missing targets', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($admin);

    $this->withToken($token)->getJson('/api/v1/admin/users')->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/users/{$member->id}")->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/users/999999')->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/admin/users/999999/suspend', ['reason' => 'probe'])->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/admin/users/999999/unsuspend', ['reason' => 'probe'])->assertNotFound();
});

it('denies verified admins on business routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $token = adminAccessTokenFor($admin);

    $this->withToken($token)->postJson('/api/v1/farms', ['name' => 'Admin Farm'])->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/farmer/contracts')->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/buyer/purchases')->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/buyer/demands', [])->assertForbidden();
});

it('denies verified admins on forum, report, and chat participation routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $token = adminAccessTokenFor($admin);

    $this->withToken($token)->postJson('/api/v1/forum/threads', [])->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/forum/reports', [])->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/chat/conversations', [])->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/chat/conversations')->assertForbidden();
});

it('lets verified admins read public forum lists without participating', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->withToken(adminAccessTokenFor($admin))
        ->getJson('/api/v1/forum/threads')
        ->assertOk();
});

it('keeps member business and participation routes working', function () {
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $category = ForumCategory::create([
        'name' => 'Access',
        'slug' => 'access-'.uniqid(),
        'description' => 'Access matrix checks',
        'icon_emoji' => '🔒',
        'sort_order' => 1,
    ]);

    $this->withToken(adminAccessTokenFor($farmer))->getJson('/api/v1/farms')->assertOk();

    Auth::forgetGuards();
    $this->withToken(adminAccessTokenFor($farmer))->postJson('/api/v1/forum/threads', [
        'title' => 'A member thread title here',
        'body' => 'A member thread body that is well over twenty characters long.',
        'category_id' => $category->id,
    ])->assertCreated();

    Auth::forgetGuards();
    $this->withToken(adminAccessTokenFor($buyer))->getJson('/api/v1/buyer/purchases')->assertOk();
});

it('lets a verified admin view their own public profile without internal fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->withToken(adminAccessTokenFor($admin))->getJson("/api/v1/users/{$admin->id}");

    $response->assertOk()->assertJsonPath('data.role', 'admin');
    expect($response->json())->not->toHaveKeys(['suspended_reason', 'password', 'remember_token']);
});

it('serializes admin user payloads with string ids and no credentials or addresses', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($admin);

    $index = $this->withToken($token)->getJson('/api/v1/admin/users');

    $index->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
    expect($index->json('data.0.id'))->toBe((string) $index->json('data.0.id'));
    expect($index->json())->not->toHaveKeys(['password', 'remember_token', 'addresses', 'tokens']);

    Auth::forgetGuards();
    $show = $this->withToken($token)->getJson("/api/v1/admin/users/{$member->id}");

    $show->assertOk()->assertJsonStructure(['data' => ['id', 'name', 'email', 'role']]);
    expect($show->json('data.id'))->toBe((string) $member->id);
    expect($show->json())->not->toHaveKeys(['password', 'remember_token', 'addresses', 'tokens']);
});

it('sends private no-store cache headers on admin user responses', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($admin);

    $index = $this->withToken($token)->getJson('/api/v1/admin/users');
    $index->assertOk();
    expect($index->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');

    Auth::forgetGuards();
    $show = $this->withToken($token)->getJson("/api/v1/admin/users/{$member->id}");
    $show->assertOk();
    expect($show->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
});

it('does not grant admin powers through a policy before hook', function () {
    expect(method_exists(AdminUserPolicy::class, 'before'))->toBeFalse();

    $reflection = new ReflectionClass(AdminUserPolicy::class);

    expect($reflection->hasMethod('before'))->toBeFalse();
});

it('filters the admin user list by role and suspension state', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $farmer = User::factory()->farmer()->create(['name' => 'Role Filter Farmer']);
    $buyer = User::factory()->buyer()->create(['name' => 'Role Filter Buyer']);
    $suspended = User::factory()->farmer()->create([
        'name' => 'Role Filter Suspended',
        'suspended_at' => now(),
        'suspended_reason' => 'matrix fixture',
    ]);
    $token = adminAccessTokenFor($admin);

    $farmers = $this->withToken($token)->getJson('/api/v1/admin/users?role=farmer');
    $farmerIds = array_column($farmers->json('data'), 'id');

    $farmers->assertOk();
    expect($farmerIds)->toContain((string) $farmer->id, (string) $suspended->id)
        ->and($farmerIds)->not->toContain((string) $buyer->id, (string) $admin->id);

    Auth::forgetGuards();
    $suspendedOnly = $this->withToken($token)->getJson('/api/v1/admin/users?suspended=true');
    $suspendedIds = array_column($suspendedOnly->json('data'), 'id');

    $suspendedOnly->assertOk();
    expect($suspendedIds)->toBe([(string) $suspended->id]);

    Auth::forgetGuards();
    $activeOnly = $this->withToken($token)->getJson('/api/v1/admin/users?suspended=false');
    $activeIds = array_column($activeOnly->json('data'), 'id');

    $activeOnly->assertOk();
    expect($activeIds)->toContain((string) $farmer->id, (string) $buyer->id)
        ->and($activeIds)->not->toContain((string) $suspended->id);

    Auth::forgetGuards();
    $members = $this->withToken($token)->getJson('/api/v1/admin/users?role=members');
    $memberIds = array_column($members->json('data'), 'id');

    $members->assertOk();
    expect($memberIds)->toContain((string) $farmer->id, (string) $buyer->id, (string) $suspended->id)
        ->and($memberIds)->not->toContain((string) $admin->id);
});

it('searches users by name or email as a literal substring', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->farmer()->create(['name' => 'Sub String Farmer', 'email' => 'substring-farmer@example.com']);
    User::factory()->buyer()->create(['name' => 'Other Buyer', 'email' => 'other-buyer@example.com']);
    User::factory()->farmer()->create(['name' => 'Hundred Percent Farmer', 'email' => 'percent-sign-%@example.com']);
    $token = adminAccessTokenFor($admin);

    $byName = $this->withToken($token)->getJson('/api/v1/admin/users?search=Sub+String');

    $byName->assertOk();
    expect($byName->json('data'))->toHaveCount(1)
        ->and($byName->json('data.0.name'))->toBe('Sub String Farmer');

    Auth::forgetGuards();
    $byEmail = $this->withToken($token)->getJson('/api/v1/admin/users?search=other-buyer@example.com');

    $byEmail->assertOk();
    expect($byEmail->json('data'))->toHaveCount(1)
        ->and($byEmail->json('data.0.email'))->toBe('other-buyer@example.com');

    Auth::forgetGuards();
    $wildcard = $this->withToken($token)->getJson('/api/v1/admin/users?search=%25');

    $wildcard->assertOk();
    expect(array_column($wildcard->json('data'), 'email'))->toBe(['percent-sign-%@example.com']);
});

it('rejects out-of-range admin list filters with 422', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $token = adminAccessTokenFor($admin);

    $cases = [
        '/api/v1/admin/users?search='.str_repeat('s', 101),
        '/api/v1/admin/users?role=superuser',
        '/api/v1/admin/users?suspended=maybe',
        '/api/v1/admin/users?page=0',
        '/api/v1/admin/users?page=10001',
        '/api/v1/admin/users?per_page=0',
        '/api/v1/admin/users?per_page=101',
    ];

    foreach ($cases as $uri) {
        Auth::forgetGuards();
        $this->withToken($token)->getJson($uri)->assertUnprocessable();
    }

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/users?search='.str_repeat('s', 100))->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/users?page=10000&per_page=100')->assertOk();
});

it('orders the admin user list newest first with a stable id tiebreak', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $first = User::factory()->farmer()->create();
    $second = User::factory()->farmer()->create();
    $token = adminAccessTokenFor($admin);

    $response = $this->withToken($token)->getJson('/api/v1/admin/users?per_page=100');

    $response->assertOk();
    $ids = array_column($response->json('data'), 'id');
    $positions = array_flip($ids);

    expect($positions[(string) $second->id])->toBeLessThan($positions[(string) $first->id]);
});
