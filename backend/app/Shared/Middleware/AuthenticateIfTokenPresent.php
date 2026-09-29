<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the Sanctum user when a bearer token is present, but never
 * rejects guest requests. Used by public marketplace endpoints that
 * personalize (nearest-first sorting, private fields) for signed-in users.
 */
class AuthenticateIfTokenPresent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== null && $request->user() === null) {
            $user = auth('sanctum')->user();

            if ($user !== null) {
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
