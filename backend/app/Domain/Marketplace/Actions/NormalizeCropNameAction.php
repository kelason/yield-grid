<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\DTOs\CropNameResolution;
use App\Domain\Marketplace\Enums\PriceMatchType;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Jobs\LearnCropAliasJob;
use Illuminate\Support\Str;

final class NormalizeCropNameAction
{
    public function __construct(
        private readonly CropReferencePriceRepositoryInterface $prices,
    ) {}

    /**
     * Resolve free-text crop input to a canonical catalog slug.
     *
     * Local-only (exact -> alias -> fuzzy) so it is safe in the HTTP
     * lifecycle. Total misses dispatch a queued AI-learning job and
     * return null; the next identical query then resolves.
     */
    public function execute(string $input): ?CropNameResolution
    {
        $slug = Str::slug(trim($input));

        if ($slug === '') {
            return null;
        }

        $catalog = $this->prices->cropCatalog();

        if (isset($catalog[$slug])) {
            return new CropNameResolution($slug, $catalog[$slug], PriceMatchType::EXACT);
        }

        $alias = $this->prices->findAlias($slug);

        if ($alias !== null && isset($catalog[$alias->crop_slug])) {
            $matchType = $alias->source === CropPriceAlias::SOURCE_AI
                ? PriceMatchType::AI_CORRECTED
                : PriceMatchType::ALIAS;

            return new CropNameResolution($alias->crop_slug, $catalog[$alias->crop_slug], $matchType, $slug);
        }

        $fuzzy = $this->fuzzyMatch($slug, $catalog);

        if ($fuzzy !== null) {
            return new CropNameResolution($fuzzy, $catalog[$fuzzy], PriceMatchType::FUZZY, $slug);
        }

        LearnCropAliasJob::dispatch($slug, trim($input));

        return null;
    }

    /**
     * @param  array<string, string>  $catalog
     */
    private function fuzzyMatch(string $slug, array $catalog): ?string
    {
        if (strlen($slug) < MarketplaceConstants::PRICE_GUIDE_FUZZY_MIN_LENGTH) {
            return null;
        }

        $best = null;
        $bestDistance = MarketplaceConstants::PRICE_GUIDE_FUZZY_MAX_DAMERAU_DISTANCE + 1;
        $bestSimilarity = 0.0;
        $bestCount = 0;

        foreach (array_keys($catalog) as $candidate) {
            if (strlen($candidate) < MarketplaceConstants::PRICE_GUIDE_FUZZY_MIN_LENGTH) {
                continue;
            }

            $distance = self::damerauDistance($slug, $candidate);

            if ($distance > MarketplaceConstants::PRICE_GUIDE_FUZZY_MAX_DAMERAU_DISTANCE) {
                continue;
            }

            similar_text($slug, $candidate, $similarity);

            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
                $bestSimilarity = $similarity;
                $bestCount = 1;
            } elseif ($distance === $bestDistance) {
                $bestCount++;

                if ($similarity > $bestSimilarity) {
                    $best = $candidate;
                    $bestSimilarity = $similarity;
                }
            }
        }

        // Equally close to two or more crops: guessing risks showing the
        // wrong crop's prices, so decline and let AI estimation handle it.
        if ($bestCount > 1) {
            return null;
        }

        return $best;
    }

    /**
     * Damerau-Levenshtein distance (optimal string alignment): adjacent
     * transpositions — the classic typo — cost 1, while two independent
     * letter changes (potato vs tomato) cost 2 and stay unmatched.
     */
    private static function damerauDistance(string $a, string $b): int
    {
        $lenA = strlen($a);
        $lenB = strlen($b);

        if ($a === $b) {
            return 0;
        }

        if ($lenA === 0) {
            return $lenB;
        }

        if ($lenB === 0) {
            return $lenA;
        }

        $prevRow = range(0, $lenB);
        $prevPrevRow = [];

        for ($i = 1; $i <= $lenA; $i++) {
            $currRow = [$i];

            for ($j = 1; $j <= $lenB; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $currRow[$j] = min($prevRow[$j] + 1, $currRow[$j - 1] + 1, $prevRow[$j - 1] + $cost);

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $currRow[$j] = min($currRow[$j], $prevPrevRow[$j - 2] + 1);
                }
            }

            $prevPrevRow = $prevRow;
            $prevRow = $currRow;
        }

        return $prevRow[$lenB];
    }
}
