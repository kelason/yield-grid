<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use Closure;
use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session/token backstop: a request that still resolves an authenticated
 * user whose account is suspended is rejected before any business logic.
 * The suspension state is always re-read from the database so revoked
 * tokens (401 upstream) and stale user instances cannot slip through.
 */
final class EnsureUserNotSuspended
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $this->isSuspended((int) $user->id)) {
            return response()->json([
                'message' => AdminConstants::SUSPENDED_MESSAGE,
                'code' => AdminConstants::SUSPENDED_CODE,
            ], HttpCode::FORBIDDEN);
        }

        return $next($request);
    }

    private function isSuspended(int $userId): bool
    {
        return User::whereKey($userId)->whereNotNull('suspended_at')->exists();
    }
}
