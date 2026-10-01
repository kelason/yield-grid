<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared building blocks for Gemini API calls: header-based authentication
 * plus secret redaction for log output, so API keys never appear in request
 * URLs (which Guzzle echoes into exception messages) or log files.
 *
 * Also owns primary → fallback model failover: every Gemini-backed feature
 * tries the configured models in order and only gives up when all fail.
 */
final class GeminiHttpHelper
{
    public const API_KEY_HEADER = 'x-goog-api-key';

    public const REDACTED = '[REDACTED]';

    private const GENERATE_CONTENT_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public static function generateContentUrl(string $model): string
    {
        return sprintf(self::GENERATE_CONTENT_URL_TEMPLATE, $model);
    }

    /**
     * @return list<string>
     */
    public static function models(): array
    {
        $models = array_filter([
            (string) config('services.gemini.model', 'gemini-3.5-flash-lite'),
            (string) config('services.gemini.fallback_model', 'gemini-3.1-flash-lite'),
        ]);

        return array_values(array_unique($models));
    }

    /**
     * POST a generateContent payload, trying each configured model in order.
     * Returns the first successful response, or null when every model fails
     * (transport exception or non-successful status).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function postGenerateContent(
        string $apiKey,
        array $payload,
        int $timeoutSeconds,
        int $maxRetries,
        int $retryDelayMs
    ): ?Response {
        $models = self::models();

        foreach ($models as $index => $model) {
            try {
                $response = Http::timeout($timeoutSeconds)
                    ->retry($maxRetries, $retryDelayMs, throw: false)
                    ->withHeaders([self::API_KEY_HEADER => $apiKey])
                    ->post(self::generateContentUrl($model), $payload);

                if ($response->successful()) {
                    return $response;
                }

                Log::warning('Gemini model request failed', ['model' => $model, 'status' => $response->status()]);
            } catch (\Throwable $e) {
                Log::warning('Gemini model request exception', [
                    'model' => $model,
                    'message' => self::redact($e->getMessage(), $apiKey),
                ]);
            }

            if ($index < count($models) - 1) {
                Log::info('Gemini falling back to next model', ['failed_model' => $model]);
            }
        }

        return null;
    }

    public static function redact(string $message, string $secret): string
    {
        if ($secret === '') {
            return $message;
        }

        return str_replace($secret, self::REDACTED, $message);
    }
}
