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
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->value;

        if ($role === null || ! in_array($role, $roles, true)) {
            return response()->json(['message' => 'Unauthorized or insufficient permissions.'], HttpCode::FORBIDDEN);
        }

        return $next($request);
    }
}
