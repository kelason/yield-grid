<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\DTOs\PriceComparisonData;
use App\Domain\Marketplace\DTOs\PriceGuideData;
use App\Domain\Marketplace\Enums\PriceCheckSource;
use App\Domain\Marketplace\Enums\PriceSource;

final class GetPriceGuideAction
{
    public const ESTIMATE_TIER_KEY = 'estimate';

    public function __construct(
        private readonly GetPriceComparisonAction $comparison,
    ) {}

    /**
     * Single-winner view of the price comparison: the first available
     * source in precedence order. Powers fairness badges and cards while
     * the full breakdown lives behind the compare endpoint.
     */
    public function execute(string $crop, ?string $regionCode = null): PriceGuideData
    {
        $comparison = $this->comparison->execute($crop, $regionCode, null);

        if (! $comparison->available || $comparison->resolvedFrom === null) {
            return PriceGuideData::unavailable(
                $comparison->reason ?? PriceGuideData::REASON_NO_PRICE_DATA,
                $comparison->input ?? trim($crop),
                $comparison->message ?? "No estimated price for '".trim($crop)."' yet.",
                $comparison->pending,
                sourcesChecked: $this->missedSources($comparison),
                resolvedFrom: null,
            );
        }

        $section = $comparison->sections[$comparison->resolvedFrom->value];

        return new PriceGuideData(
            available: true,
            sourcesChecked: $this->sourcesChecked($comparison),
            resolvedFrom: $comparison->resolvedFrom,
            cropSlug: $comparison->cropSlug,
            cropDisplayName: $comparison->cropDisplayName,
            matchType: $comparison->matchType,
            correctedFrom: $comparison->correctedFrom,
            regionCode: $regionCode,
            regionFallback: $this->regionFallback($comparison, $regionCode, $section),
            isEstimate: $comparison->resolvedFrom !== PriceCheckSource::DA,
            isStale: (bool) ($section['is_stale'] ?? false),
            observedAt: isset($section['observed_at']) ? (string) $section['observed_at'] : null,
            tiers: $this->tiers($comparison->resolvedFrom, $section),
        );
    }

    /**
     * Sources consulted in order until the resolution. A pending AI section
     * is reported via the pending flag instead of counting as checked.
     *
     * @return list<PriceCheckSource>
     */
    private function sourcesChecked(PriceComparisonData $comparison): array
    {
        $checked = [];

        foreach (PriceCheckSource::precedence() as $source) {
            if ($source === PriceCheckSource::AI && $comparison->resolvedFrom !== PriceCheckSource::AI) {
                continue;
            }

            $checked[] = $source;

            if ($source === $comparison->resolvedFrom) {
                break;
            }
        }

        return $checked;
    }

    /**
     * @return list<PriceCheckSource>
     */
    private function missedSources(PriceComparisonData $comparison): array
    {
        if ($comparison->cropSlug === null) {
            return [];
        }

        return array_values(array_filter(
            PriceCheckSource::precedence(),
            fn (PriceCheckSource $source): bool => $source !== PriceCheckSource::AI
        ));
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function regionFallback(PriceComparisonData $comparison, ?string $regionCode, array $section): bool
    {
        if ($comparison->resolvedFrom === PriceCheckSource::DA) {
            return (bool) ($section['region_fallback'] ?? false);
        }

        return $regionCode !== null;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, array{price_per_kg: float, source: string, market_name: ?string, observed_at: ?string, region_code: ?string}>
     */
    private function tiers(PriceCheckSource $resolvedFrom, array $section): array
    {
        if ($resolvedFrom !== PriceCheckSource::YIELDGRID) {
            /** @var array<string, array{price_per_kg: float, source: string, market_name: ?string, observed_at: ?string, region_code: ?string}> $tiers */
            $tiers = $section['tiers'] ?? [];

            return $tiers;
        }

        return [
            self::ESTIMATE_TIER_KEY => [
                'price_per_kg' => (float) ($section['price_per_kg'] ?? 0),
                'source' => PriceSource::MARKETPLACE_AVERAGE->value,
                'market_name' => isset($section['market_name']) ? (string) $section['market_name'] : null,
                'observed_at' => isset($section['observed_at']) ? (string) $section['observed_at'] : null,
                'region_code' => null,
            ],
        ];
    }
}
