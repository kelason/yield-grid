<?php

use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Users\Actions\SuspendUserAction;
use App\Domain\Users\Exceptions\UserSuspendedException;
use Domain\Users\Actions\LoginUserAction;
use Domain\Users\DTOs\LoginUserDTO;
use Domain\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as MarketplaceHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function suspensionTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('suspension-test-token')->plainTextToken;
}

function suspensionRaceCleanup(array $userIds): void
{
    DB::table('personal_access_tokens')->whereIn('tokenable_id', $userIds)->delete();
    DB::table('admin_action_logs')
        ->whereIn('actor_id', $userIds)
        ->orWhere(function ($query) use ($userIds): void {
            $query->where('subject_type', 'user')
                ->whereIn('subject_id', array_map(strval(...), $userIds));
        })
        ->delete();
    DB::table('users')->whereIn('id', $userIds)->delete();
    DB::purge('pgsql_race');

    if (DB::transactionLevel() === 0) {
        DB::beginTransaction();
    }
}

it('suspends a member, revokes every token, and records history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);

    $firstLogin = $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'member-pass-1']);
    $secondLogin = $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'member-pass-1']);
    $firstLogin->assertOk();
    $secondLogin->assertOk();
    $firstToken = $firstLogin->json('token');
    $secondToken = $secondLogin->json('token');

    $suspend = $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'payment fraud review']
    );

    $suspend->assertOk()
        ->assertJsonPath('data.id', (string) $member->id);
    expect($suspend->json('data.suspended_at'))->not->toBeNull();
    expect($suspend->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');

    expect($member->fresh()->suspended_reason)->toBe('payment fraud review');
    expect(DB::table('personal_access_tokens')->where('tokenable_id', $member->id)->count())->toBe(0);

    $log = AdminActionLog::where('subject_id', (string) $member->id)->firstOrFail();
    expect(AdminActionLog::count())->toBe(1)
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->action)->toBe(AdminAction::USER_SUSPENDED)
        ->and($log->subject_type)->toBe('user')
        ->and($log->reason)->toBe('payment fraud review')
        ->and($log->before)->toBe(['suspended_at' => null])
        ->and($log->after['suspended_reason'])->toBe('payment fraud review')
        ->and($log->after['suspended_at'])->not->toBeNull();

    Auth::forgetGuards();
    $this->withToken($firstToken)->getJson('/api/v1/user')->assertUnauthorized();

    Auth::forgetGuards();
    $this->withToken($secondToken)->getJson('/api/v1/user')->assertUnauthorized();

    Auth::forgetGuards();
    $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'member-pass-1'])
        ->assertForbidden()
        ->assertJson([
            'message' => 'This account is suspended. Contact support for assistance.',
            'code' => 'account_suspended',
        ]);
});

it('blocks suspended session users on authenticated routes and broadcasting auth', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();

    app(SuspendUserAction::class)->execute($admin, $member, 'session backstop');

    $this->actingAs($member->fresh())->getJson('/api/v1/user')
        ->assertForbidden()
        ->assertJson([
            'message' => 'This account is suspended. Contact support for assistance.',
            'code' => 'account_suspended',
        ]);

    $this->actingAs($member->fresh())->postJson('/api/v1/broadcasting/auth', [
        'channel_name' => 'private-thread.1',
        'socket_id' => '1.1',
    ])
        ->assertForbidden()
        ->assertJson([
            'message' => 'This account is suspended. Contact support for assistance.',
            'code' => 'account_suspended',
        ]);
});

it('keeps invalid-password failures indistinguishable for suspended accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);

    app(SuspendUserAction::class)->execute($admin, $member, 'indistinguishable failures');

    $known = $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'wrong-password']);
    $unknown = $this->postJson('/api/v1/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

    $known->assertUnprocessable()->assertJsonValidationErrors(['email']);
    $unknown->assertUnprocessable();
    expect($unknown->json('errors'))->toEqual($known->json('errors'))
        ->and($known->json('errors.email'))->toBe(['Invalid credentials.']);
});

it('leaves no web session behind a rejected suspended login', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);

    app(SuspendUserAction::class)->execute($admin, $member, 'no session residue');

    $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'member-pass-1'])
        ->assertForbidden();

    $this->assertGuest('web');
});

it('does not unsuspend through password reset', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = Password::broker()->createToken($member);

    $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'reset keeps suspension']
    )->assertOk();

    Auth::forgetGuards();
    $this->postJson('/api/v1/reset-password', [
        'token' => $token,
        'email' => $member->email,
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertOk();

    expect($member->fresh()->suspended_at)->not->toBeNull();

    $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'brand-new-pass-1'])
        ->assertForbidden()
        ->assertJson(['code' => 'account_suspended']);
});

it('does not unsuspend through email verification', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->unverified()->farmer()->create();

    app(SuspendUserAction::class)->execute($admin, $member, 'verification keeps suspension');

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $member->id,
        'hash' => sha1($member->email),
    ]);

    $this->getJson($url)->assertOk();

    $fresh = $member->fresh();
    expect($fresh->hasVerifiedEmail())->toBeTrue()
        ->and($fresh->suspended_at)->not->toBeNull();
});

it('requires a fresh login after unsuspension', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);
    $adminToken = suspensionTokenFor($admin);

    $oldToken = $this->postJson('/api/v1/login', [
        'email' => $member->email,
        'password' => 'member-pass-1',
    ])->json('token');

    Auth::forgetGuards();
    $this->withToken($adminToken)->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'temporary hold']
    )->assertOk();

    Auth::forgetGuards();
    $this->withToken($adminToken)->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => 'hold released']
    )->assertOk();

    Auth::forgetGuards();
    $this->withToken($oldToken)->getJson('/api/v1/user')->assertUnauthorized();

    $freshToken = $this->postJson('/api/v1/login', [
        'email' => $member->email,
        'password' => 'member-pass-1',
    ])->json('token');

    Auth::forgetGuards();
    $this->withToken($freshToken)->getJson('/api/v1/user')->assertOk();
});

it('rejects suspending yourself or another admin with 422', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);
    $token = suspensionTokenFor($admin);

    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$admin->id}/suspend",
        ['reason' => 'self suspension attempt']
    )->assertUnprocessable();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$otherAdmin->id}/suspend",
        ['reason' => 'peer suspension attempt']
    )->assertUnprocessable();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$otherAdmin->id}/unsuspend",
        ['reason' => 'peer unsuspend attempt']
    )->assertUnprocessable();

    expect($admin->fresh()->suspended_at)->toBeNull()
        ->and($otherAdmin->fresh()->suspended_at)->toBeNull()
        ->and(AdminActionLog::count())->toBe(0);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/users')->assertOk();
});

it('rejects repeated suspend and unsuspend transitions with 409', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();
    $token = suspensionTokenFor($admin);

    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => 'nothing to lift']
    )->assertConflict();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'first hold']
    )->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'second hold']
    )->assertConflict();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => 'hold released']
    )->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => 'release again']
    )->assertConflict();

    expect(AdminActionLog::count())->toBe(2);
});

it('enforces the 1 to 500 character reason bounds on suspend', function (string $reason, int $status) {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();

    $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => $reason]
    )->assertStatus($status);

    expect($member->fresh()->suspended_at === null)->toBe($status !== 200);
})->with([
    'empty rejected' => ['', 422],
    'whitespace rejected' => ['   ', 422],
    'one char accepted' => ['x', 200],
    '500 chars accepted' => [str_repeat('r', 500), 200],
    '501 chars rejected' => [str_repeat('r', 501), 422],
]);

it('enforces the 1 to 500 character reason bounds on unsuspend', function (string $reason, int $status) {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create();

    app(SuspendUserAction::class)->execute($admin, $member, 'reason bound fixture');

    $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => $reason]
    )->assertStatus($status);

    expect($member->fresh()->suspended_at === null)->toBe($status === 200);
})->with([
    'empty rejected' => ['', 422],
    'one char accepted' => ['y', 200],
    '500 chars accepted' => [str_repeat('r', 500), 200],
    '501 chars rejected' => [str_repeat('r', 501), 422],
]);

it('unsuspends a member and records history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);
    $token = suspensionTokenFor($admin);

    $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/suspend",
        ['reason' => 'under review']
    )->assertOk();

    Auth::forgetGuards();
    $unsuspend = $this->withToken($token)->postJson(
        "/api/v1/admin/users/{$member->id}/unsuspend",
        ['reason' => 'review cleared']
    );

    $unsuspend->assertOk()
        ->assertJsonPath('data.id', (string) $member->id);
    expect($unsuspend->json('data.suspended_at'))->toBeNull();
    expect($unsuspend->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');

    $fresh = $member->fresh();
    expect($fresh->suspended_at)->toBeNull()
        ->and($fresh->suspended_reason)->toBeNull();

    $log = AdminActionLog::where('action', AdminAction::USER_UNSUSPENDED->value)->firstOrFail();
    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject_type)->toBe('user')
        ->and($log->subject_id)->toBe((string) $member->id)
        ->and($log->reason)->toBe('review cleared')
        ->and($log->after)->toBe(['suspended_at' => null]);

    Auth::forgetGuards();
    $this->postJson('/api/v1/login', ['email' => $member->email, 'password' => 'member-pass-1'])->assertOk();
});

it('expires admin tokens after 120 minutes regardless of remember-me', function () {
    $admin = User::factory()->create(['role' => 'admin', 'password' => Hash::make('admin-pass-1')]);
    $farmer = User::factory()->farmer()->create(['password' => Hash::make('farmer-pass-1')]);

    $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'admin-pass-1',
    ])->assertOk();

    $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'admin-pass-1',
        'remember' => true,
    ])->assertOk();

    $this->postJson('/api/v1/login', [
        'email' => $farmer->email,
        'password' => 'farmer-pass-1',
        'remember' => true,
    ])->assertOk();

    $adminTokens = DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->get();

    expect($adminTokens)->toHaveCount(2);

    foreach ($adminTokens as $row) {
        $expiresAt = Carbon::parse($row->expires_at);

        expect($expiresAt->between(now()->addMinutes(119), now()->addMinutes(121)))->toBeTrue();
    }

    $rememberedFarmerToken = DB::table('personal_access_tokens')->where('tokenable_id', $farmer->id)->firstOrFail();

    expect($rememberedFarmerToken->expires_at)->toBeNull();
});

it('serializes suspension behind an in-flight login lock', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            $race->table('users')->where('id', $member->id)->lockForUpdate()->first();

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(SuspendUserAction::class)->execute($admin->fresh(), $member->fresh(), 'racing suspension');
                $this->fail('Suspension slipped past the held row lock.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }

            expect($member->fresh()->suspended_at)->toBeNull()
                ->and(AdminActionLog::count())->toBe(0);
        } finally {
            $race->rollBack();
        }

        $suspended = app(SuspendUserAction::class)->execute($admin->fresh(), $member->fresh(), 'racing suspension');

        expect($suspended->suspended_at)->not->toBeNull();
    } finally {
        suspensionRaceCleanup([$admin->id, $member->id]);
    }
});

it('serializes login behind an in-flight suspension lock', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->farmer()->create(['password' => Hash::make('member-pass-1')]);

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            $race->table('users')->where('id', $member->id)->lockForUpdate()->first();

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(LoginUserAction::class)(new LoginUserDTO($member->email, 'member-pass-1'));
                $this->fail('Login slipped past the held row lock.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }
        } finally {
            $race->rollBack();
        }

        app(SuspendUserAction::class)->execute($admin->fresh(), $member->fresh(), 'won the race');

        expect(fn () => app(LoginUserAction::class)(new LoginUserDTO($member->email, 'member-pass-1')))
            ->toThrow(UserSuspendedException::class);

        expect(DB::table('personal_access_tokens')->where('tokenable_id', $member->id)->count())->toBe(0);
        $this->assertGuest('web');
    } finally {
        suspensionRaceCleanup([$admin->id, $member->id]);
    }
});

it('treats suspended token holders as guests on the public demand list', function () {
    MarketplaceHelper::fakePsgc();
    $admin = User::factory()->create(['role' => 'admin']);
    $buyer = User::factory()->buyer()->create();
    $address = MarketplaceHelper::makeAddress($buyer);
    $buyerToken = suspensionTokenFor($buyer);

    $this->withToken($buyerToken)->postJson('/api/v1/buyer/demands', [
        'title' => '600kg fresh tomatoes',
        'description' => 'For weekend market',
        'crop_name' => 'Tomato',
        'quantity_kg' => 600,
        'target_price_per_kg' => 45,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $address->id,
    ])->assertCreated();

    Auth::forgetGuards();
    $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$buyer->id}/suspend",
        ['reason' => 'guest parity fixture']
    )->assertOk();

    $staleToken = $buyer->fresh()->createToken('stale-token')->plainTextToken;

    Auth::forgetGuards();
    $guest = $this->getJson('/api/v1/market/demands');

    Auth::forgetGuards();
    $suspended = $this->withToken($staleToken)->getJson('/api/v1/market/demands');

    $guest->assertOk();
    $suspended->assertOk();
    expect($suspended->json())->toEqual($guest->json());
});

it('hides personalized demand fields from suspended token holders', function () {
    MarketplaceHelper::fakePsgc();
    $admin = User::factory()->create(['role' => 'admin']);
    $buyer = User::factory()->buyer()->create();
    $address = MarketplaceHelper::makeAddress($buyer);
    $buyerToken = suspensionTokenFor($buyer);

    $demandId = $this->withToken($buyerToken)->postJson('/api/v1/buyer/demands', [
        'title' => '400kg red onions',
        'description' => 'For pantry stock',
        'crop_name' => 'Red Onion',
        'quantity_kg' => 400,
        'target_price_per_kg' => 60,
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'address_id' => $address->id,
    ])->assertCreated()->json('data.id');

    Auth::forgetGuards();
    $this->withToken(suspensionTokenFor($admin))->postJson(
        "/api/v1/admin/users/{$buyer->id}/suspend",
        ['reason' => 'guest parity fixture']
    )->assertOk();

    $staleToken = $buyer->fresh()->createToken('stale-token')->plainTextToken;

    Auth::forgetGuards();
    $guest = $this->getJson("/api/v1/market/demands/{$demandId}");

    Auth::forgetGuards();
    $suspended = $this->withToken($staleToken)->getJson("/api/v1/market/demands/{$demandId}");

    $guest->assertOk();
    $suspended->assertOk();
    expect($suspended->json())->toEqual($guest->json())
        ->and($suspended->json('data.delivery_address'))->toBeNull();
});
