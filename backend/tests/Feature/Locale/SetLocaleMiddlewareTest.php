<?php

declare(strict_types=1);

use App\Shared\Middleware\SetLocale;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function runSetLocale(?User $user, ?string $acceptLanguage): string
{
    $request = Request::create('/api/v1/user', 'GET');
    if ($acceptLanguage !== null) {
        $request->headers->set('Accept-Language', $acceptLanguage);
    }
    $request->setUserResolver(fn () => $user);

    (new SetLocale)->handle($request, fn () => response()->json(['ok' => true]));

    return App::getLocale();
}

it('maps the guest Accept-Language header to Cebuano', function (): void {
    expect(runSetLocale(null, 'ceb-PH'))->toBe('ceb');
});

it('maps Filipino headers to Tagalog like the frontend', function (): void {
    expect(runSetLocale(null, 'fil-PH,fil;q=0.9'))->toBe('tl');
});

it('falls back to English for unsupported header values', function (): void {
    expect(runSetLocale(null, 'es'))->toBe('en');
});

it('falls back to English without a header', function (): void {
    expect(runSetLocale(null, null))->toBe('en');
});

it('ignores the authenticated user (applied later by SetUserLocale)', function (): void {
    $user = User::factory()->create(['locale' => 'ceb']);

    expect(runSetLocale($user, 'tl'))->toBe('tl');
});
