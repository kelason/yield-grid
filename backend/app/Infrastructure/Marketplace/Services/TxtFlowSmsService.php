<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Services;

use App\Domain\Marketplace\DTOs\SmsResult;
use App\Domain\Marketplace\Services\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * TxtFlow (Android SMS gateway) implementation — scaffolded, not yet live.
 *
 * The TxtFlow API payload is intentionally NOT finalized here: their docs
 * site is a JS shell with no inspectable API reference, and the owner's
 * phone is not set up yet. When the device is ready, fill in the request
 * below from the TxtFlow dashboard docs and flip TXTFLOW_ENABLED=true.
 */
final class TxtFlowSmsService implements SmsServiceInterface
{
    public const PROVIDER = 'txtflow';

    public function isConfigured(): bool
    {
        return (bool) config('services.txtflow.enabled', false)
            && (string) config('services.txtflow.api_key', '') !== ''
            && (string) config('services.txtflow.base_url', '') !== '';
    }

    public function send(string $to, string $message): SmsResult
    {
        if (! $this->isConfigured()) {
            Log::info('SMS skipped: TxtFlow is not configured.', ['to' => $to]);

            return new SmsResult(false, self::PROVIDER, null, 'TxtFlow is not configured.');
        }

        // TODO(txtflow): implement the real send call once the device is set
        // up — see https://www.txtflow.xyz/ dashboard API docs for the exact
        // endpoint + payload, then Http::post() it here.
        Log::warning('SMS not sent: TxtFlow payload is not finalized.', ['to' => $to]);

        return new SmsResult(false, self::PROVIDER, null, 'TxtFlow send payload is not finalized.');
    }
}
