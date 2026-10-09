<?php

declare(strict_types=1);

use App\Shared\Middleware\SetLocale;
use App\Shared\Middleware\SetUserLocale;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function runLocaleStack(?User $user, ?string $acceptLanguage): string
{
    $request = Request::create('/api/v1/user', 'GET');
    if ($acceptLanguage !== null) {
        $request->headers->set('Accept-Language', $acceptLanguage);
    }
    $request->setUserResolver(fn () => $user);

    $next = fn () => response()->json(['ok' => true]);
    (new SetLocale)->handle($request, fn () => (new SetUserLocale)->handle($request, $next));

    return App::getLocale();
}

it('prefers the authenticated user locale over the header', function (): void {
    $user = User::factory()->create(['locale' => 'ceb']);

    expect(runLocaleStack($user, 'tl'))->toBe('ceb');
});

it('keeps the header locale when the user locale is unsupported', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['locale' => 'es'])->save();

    expect(runLocaleStack($user, 'tl'))->toBe('tl');
});

it('leaves guests on the header locale', function (): void {
    expect(runLocaleStack(null, 'tl'))->toBe('tl');
});
