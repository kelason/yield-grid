<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class EloquentCropReferencePriceRepository implements CropReferencePriceRepositoryInterface
{
    public function latestBySlugAndRegion(string $slug, ?string $regionCode): Collection
    {
        $rows = CropReferencePrice::where('crop_slug', $slug)
            ->where(function ($query) use ($regionCode): void {
                $query->whereNull('region_code');
                if ($regionCode !== null) {
                    $query->orWhere('region_code', $regionCode);
                }
            })
            ->orderByDesc('observed_at')
            ->get();

        /** @var array<string, CropReferencePrice> $picked */
        $picked = [];

        foreach ($rows as $row) {
            $tier = $row->tier->value;
            if (! isset($picked[$tier])) {
                $picked[$tier] = $row;

                continue;
            }

            $current = $picked[$tier];
            $rowIsRegional = $row->region_code !== null && $row->region_code === $regionCode;
            $currentIsRegional = $current->region_code !== null && $current->region_code === $regionCode;

            if ($rowIsRegional && ! $currentIsRegional) {
                $picked[$tier] = $row;
            }
        }

        return collect(array_values($picked));
    }

    public function aiEstimatesBySlug(string $slug): Collection
    {
        return CropReferencePrice::where('crop_slug', $slug)
            ->where('source', PriceSource::AI_ESTIMATE)
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findAlias(string $aliasSlug): ?CropPriceAlias
    {
        return CropPriceAlias::where('alias_slug', $aliasSlug)->first();
    }

    public function cropCatalog(): array
    {
        /** @var array<string, string> */
        return Cache::remember(
            'crop_price_catalog',
            MarketplaceConstants::PRICE_GUIDE_CATALOG_CACHE_TTL_SECONDS,
            function (): array {
                return CropReferencePrice::query()
                    ->orderByDesc('observed_at')
                    ->get(['crop_slug', 'crop_display_name'])
                    ->reduce(function (array $carry, CropReferencePrice $row): array {
                        $carry[$row->crop_slug] ??= $row->crop_display_name;

                        return $carry;
                    }, []);
            }
        );
    }

    public function upsertRows(array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $query = CropReferencePrice::where('crop_slug', $row['crop_slug'])
                ->where('tier', $row['tier'])
                ->where('observed_at', $row['observed_at']);

            if (($row['region_code'] ?? null) === null) {
                $query->whereNull('region_code');
            } else {
                $query->where('region_code', $row['region_code']);
            }

            $query->updateOrCreate([], [
                'crop_slug' => $row['crop_slug'],
                'crop_display_name' => $row['crop_display_name'],
                'tier' => $row['tier'],
                'price_per_kg' => $row['price_per_kg'],
                'currency' => $row['currency'] ?? 'PHP',
                'region_code' => $row['region_code'] ?? null,
                'market_name' => $row['market_name'] ?? null,
                'source' => $row['source'],
                'observed_at' => $row['observed_at'],
            ]);

            $count++;
        }

        if ($count > 0) {
            Cache::forget('crop_price_catalog');
        }

        return $count;
    }

    public function marketplaceAverage(string $slug, int $days): ?array
    {
        $since = now()->subDays($days)->toDateString();
        $limit = MarketplaceConstants::PRICE_GUIDE_MARKETPLACE_AVG_MAX_ROWS;

        $contracts = ForwardContract::where('created_at', '>=', $since)
            ->limit($limit)
            ->get(['crop_name', 'price_per_kg']);

        $listings = HarvestListing::where('created_at', '>=', $since)
            ->limit($limit)
            ->get(['crop_name', 'price_per_kg']);

        $prices = $contracts->concat($listings)
            ->filter(fn ($row): bool => $this->listingMatchesSlug((string) $row->crop_name, $slug))
            ->map(fn ($row): float => (float) $row->price_per_kg)
            ->filter(fn (float $price): bool => $price > 0);

        if ($prices->isEmpty()) {
            return null;
        }

        return ['price' => round($prices->avg(), 2), 'count' => $prices->count()];
    }

    public function createAlias(string $aliasSlug, string $cropSlug, string $source, bool $verified): CropPriceAlias
    {
        return CropPriceAlias::firstOrCreate(
            ['alias_slug' => $aliasSlug],
            ['crop_slug' => $cropSlug, 'source' => $source, 'verified' => $verified]
        );
    }

    private function listingMatchesSlug(string $cropName, string $slug): bool
    {
        $listingSlug = Str::slug($cropName);

        if ($listingSlug === $slug) {
            return true;
        }

        $alias = $this->findAlias($listingSlug);

        return $alias !== null && $alias->crop_slug === $slug;
    }
}
