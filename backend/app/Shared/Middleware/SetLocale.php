<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Constants\LocaleConstants;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Resolves Accept-Language only and never touches auth guards: this
     * middleware runs on the api group before route auth middleware, and
     * resolving the user here poisons guard state. Authenticated users
     * get their profile locale applied after auth by SetUserLocale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(
            $this->headerLocale((string) $request->headers->get('Accept-Language', ''))
                ?? LocaleConstants::DEFAULT
        );

        return $next($request);
    }

    private function headerLocale(string $header): ?string
    {
        $primary = strtolower(trim(explode(',', $header)[0] ?? ''));
        $primary = explode(';', $primary)[0];

        foreach (LocaleConstants::SUPPORTED as $supported) {
            if ($primary === $supported || str_starts_with($primary, $supported.'-')) {
                return $supported;
            }
        }

        if ($primary === 'fil' || str_starts_with($primary, 'fil-')) {
            return 'tl';
        }

        return null;
    }
}
