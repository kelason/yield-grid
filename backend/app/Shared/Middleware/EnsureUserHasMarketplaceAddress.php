<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Constants\HttpCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasMarketplaceAddress
{
    public const ERROR_CODE = 'MARKETPLACE_ADDRESS_REQUIRED';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasMarketplaceAddress()) {
            return response()->json([
                'message' => 'A saved address is required to trade in the marketplace. Please add one in your profile first.',
                'error_code' => self::ERROR_CODE,
            ], HttpCode::UNPROCESSABLE_ENTITY);
        }

        return $next($request);
    }
}
