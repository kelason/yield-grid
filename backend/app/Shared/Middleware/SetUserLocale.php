<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Constants\LocaleConstants;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * Applies the authenticated user's profile locale. Must run after the
     * auth:sanctum middleware: resolving the user any earlier poisons
     * guard state for the rest of the request lifecycle.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $request->user('sanctum')?->locale;

        if (in_array($userLocale, LocaleConstants::SUPPORTED, true)) {
            App::setLocale($userLocale);
        }

        return $next($request);
    }
}
