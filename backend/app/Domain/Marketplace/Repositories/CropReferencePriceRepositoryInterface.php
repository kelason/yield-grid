<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Repositories;

use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropReferencePrice;
use Illuminate\Support\Collection;

interface CropReferencePriceRepositoryInterface
{
    /**
     * Latest row per tier for a crop, preferring the given region with
     * national (null region) fallback per tier.
     *
     * @return Collection<int, CropReferencePrice>
     */
    public function latestBySlugAndRegion(string $slug, ?string $regionCode): Collection;

    /**
     * AI estimate rows for a crop, newest first. Separate from
     * latestBySlugAndRegion because per-tier picks would hide AI rows
     * behind same-tier DA rows.
     *
     * @return Collection<int, CropReferencePrice>
     */
    public function aiEstimatesBySlug(string $slug): Collection;

    public function findAlias(string $aliasSlug): ?CropPriceAlias;

    /**
     * Known crop catalog: slug => display name.
     *
     * @return array<string, string>
     */
    public function cropCatalog(): array;

    /**
     * @param  list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency?: string, region_code?: ?string, market_name?: ?string, source: string, observed_at: string}>  $rows
     */
    public function upsertRows(array $rows): int;

    /**
     * Average farmer asking price across marketplace listings for a crop,
     * used as a clearly-labelled estimate when no DA row exists.
     *
     * @return ?array{price: float, count: int}
     */
    public function marketplaceAverage(string $slug, int $days): ?array;

    public function createAlias(string $aliasSlug, string $cropSlug, string $source, bool $verified): CropPriceAlias;
}
