<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

use App\Domain\Marketplace\Enums\PriceCheckSource;
use App\Domain\Marketplace\Enums\PriceMatchType;

final readonly class PriceGuideData
{
    public const REASON_UNKNOWN_CROP = 'unknown_crop';

    public const REASON_NO_PRICE_DATA = 'no_price_data';

    /**
     * @param  PriceCheckSource[]  $sourcesChecked  Resolution path consulted, in order.
     * @param  array<string, array{price_per_kg: float, source: string, market_name: ?string, observed_at: ?string, region_code: ?string}>  $tiers
     */
    public function __construct(
        public bool $available,
        public ?string $reason = null,
        public ?string $message = null,
        public ?string $input = null,
        public bool $pending = false,
        public array $sourcesChecked = [],
        public ?PriceCheckSource $resolvedFrom = null,
        public ?string $cropSlug = null,
        public ?string $cropDisplayName = null,
        public ?PriceMatchType $matchType = null,
        public ?string $correctedFrom = null,
        public ?string $regionCode = null,
        public bool $regionFallback = false,
        public bool $isEstimate = false,
        public bool $isStale = false,
        public ?string $observedAt = null,
        public array $tiers = [],
    ) {}

    /**
     * @param  PriceCheckSource[]  $sourcesChecked
     */
    public static function unavailable(
        string $reason,
        string $input,
        string $message,
        bool $pending = false,
        array $sourcesChecked = [],
        ?PriceCheckSource $resolvedFrom = null,
    ): self {
        return new self(
            available: false,
            reason: $reason,
            message: $message,
            input: $input,
            pending: $pending,
            sourcesChecked: $sourcesChecked,
            resolvedFrom: $resolvedFrom,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (! $this->available) {
            return [
                'available' => false,
                'reason' => $this->reason,
                'message' => $this->message,
                'input' => $this->input,
                'pending' => $this->pending,
                'sources_checked' => array_map(fn (PriceCheckSource $source): string => $source->value, $this->sourcesChecked),
                'resolved_from' => $this->resolvedFrom?->value,
            ];
        }

        return [
            'available' => true,
            'sources_checked' => array_map(fn (PriceCheckSource $source): string => $source->value, $this->sourcesChecked),
            'resolved_from' => $this->resolvedFrom?->value,
            'crop_slug' => $this->cropSlug,
            'crop_display_name' => $this->cropDisplayName,
            'match_type' => $this->matchType?->value,
            'corrected_from' => $this->correctedFrom,
            'region_code' => $this->regionCode,
            'region_fallback' => $this->regionFallback,
            'is_estimate' => $this->isEstimate,
            'is_stale' => $this->isStale,
            'observed_at' => $this->observedAt,
            'tiers' => $this->tiers,
        ];
    }
}
