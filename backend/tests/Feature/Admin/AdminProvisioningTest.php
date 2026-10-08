<?php

use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates an unverified dedicated admin with a hashed password and no address, farm, or token', function () {
    Notification::fake();

    $password = 'admin-secret-123';

    $this->artisan('admin:create', ['email' => 'operator@example.test'])
        ->expectsQuestion('Name', 'Site Operator')
        ->expectsQuestion('Password', $password)
        ->expectsQuestion('Confirm password', $password)
        ->expectsOutputToContain('Admin account created for operator@example.test.')
        ->assertSuccessful();

    $user = User::where('email', 'operator@example.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::ADMIN)
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and($user->password)->not->toBe($password)
        ->and($user->addresses()->count())->toBe(0)
        ->and($user->farms()->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('records an admin_created history entry without credentials', function () {
    $this->artisan('admin:create', ['email' => 'history@example.test'])
        ->expectsQuestion('Name', 'History Operator')
        ->expectsQuestion('Password', 'history-secret-1')
        ->expectsQuestion('Confirm password', 'history-secret-1')
        ->assertSuccessful();

    $user = User::where('email', 'history@example.test')->firstOrFail();
    $log = AdminActionLog::where('subject_id', (string) $user->id)->firstOrFail();

    expect(AdminActionLog::count())->toBe(1)
        ->and($log->actor_id)->toBeNull()
        ->and($log->action)->toBe(AdminAction::ADMIN_CREATED)
        ->and($log->subject_type)->toBe('user')
        ->and($log->before)->toBe([])
        ->and($log->after)->toBe(['role' => 'admin']);
});

it('rejects an existing buyer email without changing the account', function () {
    User::factory()->buyer()->create([
        'email' => 'buyer@example.test',
        'password' => Hash::make('buyer-original-1'),
    ]);

    $this->artisan('admin:create', ['email' => 'buyer@example.test'])
        ->expectsOutputToContain('already registered')
        ->assertFailed();

    expect(User::count())->toBe(1)
        ->and(AdminActionLog::count())->toBe(0);

    $fresh = User::where('email', 'buyer@example.test')->firstOrFail();

    expect($fresh->role)->toBe(UserRole::BUYER)
        ->and(Hash::check('buyer-original-1', $fresh->password))->toBeTrue();
});

it('rejects an existing farmer email without changing the account', function () {
    User::factory()->farmer()->create([
        'email' => 'farmer@example.test',
        'password' => Hash::make('farmer-original-1'),
    ]);

    $this->artisan('admin:create', ['email' => 'farmer@example.test'])
        ->expectsOutputToContain('already registered')
        ->assertFailed();

    expect(User::count())->toBe(1)
        ->and(AdminActionLog::count())->toBe(0);

    $fresh = User::where('email', 'farmer@example.test')->firstOrFail();

    expect($fresh->role)->toBe(UserRole::FARMER)
        ->and(Hash::check('farmer-original-1', $fresh->password))->toBeTrue();
});

it('treats an existing admin email as an informational no-op', function () {
    User::factory()->create([
        'email' => 'admin@example.test',
        'role' => 'admin',
        'password' => Hash::make('admin-original-1'),
        'email_verified_at' => null,
    ]);

    $this->artisan('admin:create', ['email' => 'admin@example.test'])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    expect(User::count())->toBe(1)
        ->and(AdminActionLog::count())->toBe(0)
        ->and(Hash::check('admin-original-1', User::where('email', 'admin@example.test')->firstOrFail()->password))->toBeTrue();
});

it('keeps the password out of console output and the log file', function () {
    $password = 'super-secret-admin-pw';

    $this->artisan('admin:create', ['email' => 'clean@example.test'])
        ->expectsQuestion('Name', 'Clean Operator')
        ->expectsQuestion('Password', $password)
        ->expectsQuestion('Confirm password', $password)
        ->expectsOutput('Admin account created for clean@example.test.')
        ->assertSuccessful();

    $logPath = storage_path('logs/laravel.log');

    if (file_exists($logPath)) {
        expect((string) file_get_contents($logPath))->not->toContain($password);
    }
});

it('enforces the 12 to 255 character admin password bounds', function (string $password, bool $valid) {
    $pending = $this->artisan('admin:create', ['email' => 'bounds@example.test'])
        ->expectsQuestion('Name', 'Bounds Operator')
        ->expectsQuestion('Password', $password)
        ->expectsQuestion('Confirm password', $password);

    $valid ? $pending->assertSuccessful() : $pending->assertFailed();
    $pending->run();

    expect(User::where('email', 'bounds@example.test')->exists())->toBe($valid);
})->with([
    '11 chars rejected' => [str_repeat('a', 11), false],
    '12 chars accepted' => [str_repeat('a', 12), true],
    '255 chars accepted' => [str_repeat('a', 255), true],
    '256 chars rejected' => [str_repeat('a', 256), false],
]);

it('rejects a blank name', function () {
    $this->artisan('admin:create', ['email' => 'blank@example.test'])
        ->expectsQuestion('Name', '   ')
        ->expectsQuestion('Password', 'valid-password-1')
        ->expectsQuestion('Confirm password', 'valid-password-1')
        ->assertFailed();

    expect(User::where('email', 'blank@example.test')->exists())->toBeFalse();
});

it('rejects a malformed email', function () {
    $this->artisan('admin:create', ['email' => 'not-an-email'])->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects a mismatched password confirmation', function () {
    $this->artisan('admin:create', ['email' => 'mismatch@example.test'])
        ->expectsQuestion('Name', 'Mismatch Operator')
        ->expectsQuestion('Password', 'first-password-1')
        ->expectsQuestion('Confirm password', 'second-password-2')
        ->expectsOutputToContain('do not match')
        ->assertFailed();

    expect(User::where('email', 'mismatch@example.test')->exists())->toBeFalse();
});

it('appends history entries through the repository', function () {
    $repository = app(AdminActionLogRepositoryInterface::class);

    $created = $repository->append(null, AdminAction::ADMIN_CREATED, 'user', '7', 'console provisioning', [], ['role' => 'admin']);

    expect($created->exists)->toBeTrue()
        ->and($created->actor_id)->toBeNull()
        ->and($created->related_report_id)->toBeNull();

    $actor = User::factory()->create();

    $suspended = $repository->append($actor->id, AdminAction::USER_SUSPENDED, 'user', '9', 'spam', ['suspended' => false], ['suspended' => true], 3);

    expect($suspended->actor_id)->toBe($actor->id)
        ->and($suspended->action)->toBe(AdminAction::USER_SUSPENDED)
        ->and($suspended->before)->toBe(['suspended' => false])
        ->and($suspended->after)->toBe(['suspended' => true])
        ->and($suspended->related_report_id)->toBe(3);
});
