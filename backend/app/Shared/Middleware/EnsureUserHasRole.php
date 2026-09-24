<?php

namespace App\Shared\Middleware;

use App\Constants\HttpCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role->value !== $role) {
            return response()->json(['message' => 'Unauthorized or insufficient permissions.'], HttpCode::FORBIDDEN);
        }

        return $next($request);
    }
}
