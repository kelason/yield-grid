<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\DTOs\CropNameResolution;
use App\Domain\Marketplace\DTOs\PriceComparisonData;
use App\Domain\Marketplace\DTOs\PriceGuideData;
use App\Domain\Marketplace\Enums\PriceCheckSource;
use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Jobs\GenerateAiPriceEstimateJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GetPriceComparisonAction
{
    public function __construct(
        private readonly NormalizeCropNameAction $normalizer,
        private readonly CropReferencePriceRepositoryInterface $prices,
    ) {}

    /**
     * Builds one section per requested source so the UI can load and show
     * YieldGrid, DA, and AI prices side by side instead of a single winner.
     *
     * @param  list<string>|null  $sources  PriceCheckSource values; null means all.
     */
    public function execute(string $crop, ?string $regionCode = null, ?array $sources = null): PriceComparisonData
    {
        $wanted = $this->wantedSources($sources);
        $resolution = $this->normalizer->execute($crop);

        if ($resolution === null) {
            return $this->unknownCrop($crop, $wanted);
        }

        try {
            /** @var Collection<int, CropReferencePrice> $rows */
            $rows = $this->prices->latestBySlugAndRegion($resolution->slug, $regionCode);
        } catch (\Throwable $e) {
            Log::error('Crop price reference lookup failed', [
                'slug' => $resolution->slug,
                'error' => $e->getMessage(),
            ]);
            $rows = collect();
        }

        $sections = [];

        foreach ($wanted as $source) {
            try {
                $sections[$source->value] = match ($source) {
                    PriceCheckSource::YIELDGRID => $this->yieldgridSection($resolution->slug),
                    PriceCheckSource::DA => $this->daSection($rows, $resolution->slug, $regionCode),
                    PriceCheckSource::AI => $this->aiSection($resolution),
                };
            } catch (\Throwable $e) {
                Log::error('Crop price '.self::sourceLabel($source).' section failed', [
                    'slug' => $resolution->slug,
                    'error' => $e->getMessage(),
                ]);
                $sections[$source->value] = ['available' => false];
            }
        }

        $resolvedFrom = null;

        foreach ($wanted as $source) {
            if (($sections[$source->value]['available'] ?? false) === true) {
                $resolvedFrom = $source;

                break;
            }
        }

        $available = $resolvedFrom !== null;

        return new PriceComparisonData(
            available: $available,
            reason: $available ? null : PriceGuideData::REASON_NO_PRICE_DATA,
            message: $available ? null : "No estimated price for '{$resolution->displayName}' yet.",
            input: $available ? null : trim($crop),
            pending: $this->aiPending($sections),
            resolvedFrom: $resolvedFrom,
            cropSlug: $resolution->slug,
            cropDisplayName: $resolution->displayName,
            matchType: $resolution->matchType,
            correctedFrom: $resolution->correctedFrom,
            sections: $sections,
        );
    }

    /**
     * @param  list<PriceCheckSource>  $wanted
     */
    private function unknownCrop(string $crop, array $wanted): PriceComparisonData
    {
        try {
            GenerateAiPriceEstimateJob::dispatch(Str::slug(trim($crop)), trim($crop));
        } catch (\Throwable $e) {
            Log::warning('Crop price estimate dispatch failed; continuing unavailable.', [
                'crop' => trim($crop),
                'error' => $e->getMessage(),
            ]);
        }

        $sections = [];

        foreach ($wanted as $source) {
            $sections[$source->value] = $source === PriceCheckSource::AI
                ? ['available' => false, 'pending' => $this->estimatesEnabled()]
                : ['available' => false];
        }

        return new PriceComparisonData(
            available: false,
            reason: PriceGuideData::REASON_UNKNOWN_CROP,
            message: "No estimated price for '".trim($crop)."' yet.",
            input: trim($crop),
            pending: $this->aiPending($sections),
            resolvedFrom: null,
            sections: $sections,
        );
    }

    /**
     * @param  list<string>|null  $sources
     * @return list<PriceCheckSource>
     */
    private function wantedSources(?array $sources): array
    {
        $precedence = PriceCheckSource::precedence();

        if ($sources === null) {
            return $precedence;
        }

        $requested = array_map(
            fn (string $source): PriceCheckSource => PriceCheckSource::from($source),
            array_values($sources)
        );

        return array_values(array_filter(
            $precedence,
            fn (PriceCheckSource $source): bool => in_array($source, $requested, true)
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     */
    private function aiPending(array $sections): bool
    {
        return ($sections[PriceCheckSource::AI->value]['pending'] ?? false) === true;
    }

    /**
     * @return array<string, mixed>
     */
    private function yieldgridSection(string $slug): array
    {
        $startTime = microtime(true);
        $average = $this->prices->marketplaceAverage(
            $slug,
            MarketplaceConstants::PRICE_GUIDE_MARKETPLACE_AVG_DAYS
        );

        $section = $average === null
            ? ['available' => false]
            : [
                'available' => true,
                'price_per_kg' => $average['price'],
                'listings_count' => $average['count'],
                'market_name' => "YieldGrid marketplace ({$average['count']} listings)",
                'observed_at' => now()->toDateString(),
            ];

        Log::info('Crop price YieldGrid section completed', [
            'latency_ms' => round((microtime(true) - $startTime) * 1000),
            'slug' => $slug,
            'available' => $average !== null,
            'listings' => $average['count'] ?? 0,
        ]);

        return $section;
    }

    /**
     * @param  Collection<int, CropReferencePrice>  $rows
     * @return array<string, mixed>
     */
    private function daSection(Collection $rows, string $slug, ?string $regionCode): array
    {
        $startTime = microtime(true);
        $authoritative = $rows
            ->reject(fn (CropReferencePrice $row): bool => $row->source === PriceSource::AI_ESTIMATE)
            ->values();

        if ($authoritative->isEmpty()) {
            Log::info('Crop price DA section completed', [
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'slug' => $slug,
                'available' => false,
                'tiers' => 0,
            ]);

            return ['available' => false];
        }

        [$tiers, $latestObserved] = $this->buildTiers($authoritative);
        $regionalHit = $regionCode !== null
            && $authoritative->contains(fn (CropReferencePrice $row): bool => $row->region_code === $regionCode);
        $staleCutoff = now()->subDays(MarketplaceConstants::PRICE_GUIDE_STALE_AFTER_DAYS)->toDateString();

        Log::info('Crop price DA section completed', [
            'latency_ms' => round((microtime(true) - $startTime) * 1000),
            'slug' => $slug,
            'available' => true,
            'tiers' => count($tiers),
        ]);

        return [
            'available' => true,
            'price_per_kg' => $this->primaryPrice($tiers),
            'tiers' => $tiers,
            'is_stale' => $latestObserved !== null && $latestObserved < $staleCutoff,
            'observed_at' => $latestObserved,
            'region_code' => $regionCode,
            'region_fallback' => $regionCode !== null && ! $regionalHit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aiSection(CropNameResolution $resolution): array
    {
        $aiRows = $this->prices->aiEstimatesBySlug($resolution->slug);
        $fresh = $aiRows->filter(fn (CropReferencePrice $row): bool => $row->observed_at->isToday())->values();

        if ($fresh->isNotEmpty()) {
            [$tiers, $latestObserved] = $this->buildTiers($fresh);

            return [
                'available' => true,
                'pending' => false,
                'price_per_kg' => $this->primaryPrice($tiers),
                'tiers' => $tiers,
                'is_stale' => false,
                'observed_at' => $latestObserved,
            ];
        }

        GenerateAiPriceEstimateJob::dispatch($resolution->slug, $resolution->displayName);

        $expired = $aiRows->reject(fn (CropReferencePrice $row): bool => $row->observed_at->isToday())->values();

        if ($expired->isNotEmpty()) {
            [$tiers, $latestObserved] = $this->buildTiers($expired);

            return [
                'available' => true,
                'pending' => $this->estimatesEnabled(),
                'price_per_kg' => $this->primaryPrice($tiers),
                'tiers' => $tiers,
                'is_stale' => true,
                'observed_at' => $latestObserved,
            ];
        }

        return ['available' => false, 'pending' => $this->estimatesEnabled()];
    }

    /**
     * @param  Collection<int, CropReferencePrice>  $rows  Newest first; first row wins each tier.
     * @return array{0: array<string, array{price_per_kg: float, source: string, market_name: ?string, observed_at: ?string, region_code: ?string}>, 1: ?string}
     */
    private function buildTiers(Collection $rows): array
    {
        $tiers = [];
        $latestObserved = null;

        foreach ($rows as $row) {
            $observed = $row->observed_at->toDateString();

            if ($latestObserved === null || $observed > $latestObserved) {
                $latestObserved = $observed;
            }

            $tier = $row->tier->value;

            if (! isset($tiers[$tier])) {
                $tiers[$tier] = [
                    'price_per_kg' => (float) $row->price_per_kg,
                    'source' => $row->source->value,
                    'market_name' => $row->market_name,
                    'observed_at' => $observed,
                    'region_code' => $row->region_code,
                ];
            }
        }

        return [$tiers, $latestObserved];
    }

    /**
     * Headline price prefers the tier a farmer would sell at.
     *
     * @param  array<string, array{price_per_kg: float}>  $tiers
     */
    private function primaryPrice(array $tiers): ?float
    {
        foreach (self::primaryTierOrder() as $tier) {
            if (isset($tiers[$tier])) {
                return (float) $tiers[$tier]['price_per_kg'];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function primaryTierOrder(): array
    {
        return [PriceTier::FARMGATE->value, PriceTier::WHOLESALE->value, PriceTier::RETAIL->value];
    }

    private static function sourceLabel(PriceCheckSource $source): string
    {
        return match ($source) {
            PriceCheckSource::YIELDGRID => 'YieldGrid',
            PriceCheckSource::DA => 'DA',
            PriceCheckSource::AI => 'AI',
        };
    }

    private function estimatesEnabled(): bool
    {
        return (bool) config('services.ai_estimates.enabled', true);
    }
}
