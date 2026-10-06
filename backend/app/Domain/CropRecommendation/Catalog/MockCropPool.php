<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Catalog;

use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

/**
 * Candidate-pool resolution: narrow to the selected subtypes, broadening
 * to their produce types and then the full catalog so thin pools still
 * fill ten slots.
 */
final class MockCropPool
{
    private const FILL_TARGET = 10;

    /**
     * @param  list<string>  $subtypes
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    public static function resolve(array $subtypes): array
    {
        if ($subtypes === []) {
            return [MockCropCatalog::entries(), []];
        }

        $pool = [];
        $preferred = [];

        foreach (MockCropCatalog::entries() as $entry) {
            $subtype = CropTaxonomy::profile($entry['slug'])['subtype'] ?? null;

            if (in_array($subtype, $subtypes, true)) {
                $pool[] = $entry;
                $preferred[] = $entry['slug'];
            }
        }

        if (count($pool) < self::FILL_TARGET) {
            $pool = array_merge($pool, self::sameTypeEntries($subtypes, $preferred));
        }

        if (count($pool) < self::FILL_TARGET) {
            $pool = array_merge($pool, self::remainingEntries($pool));
        }

        return [$pool, $preferred];
    }

    /**
     * @param  list<string>  $subtypes
     * @param  list<string>  $exclude
     * @return list<array<string, mixed>>
     */
    private static function sameTypeEntries(array $subtypes, array $exclude): array
    {
        $types = [];

        foreach ($subtypes as $subtype) {
            $types[] = CropTaxonomy::subtypes()[$subtype]['type'] ?? null;
        }

        $fillers = [];

        foreach (MockCropCatalog::entries() as $entry) {
            $type = CropTaxonomy::profile($entry['slug'])['type'] ?? null;

            if (! in_array($entry['slug'], $exclude, true) && in_array($type, $types, true)) {
                $fillers[] = $entry;
            }
        }

        return $fillers;
    }

    /**
     * @param  list<array<string, mixed>>  $pool
     * @return list<array<string, mixed>>
     */
    private static function remainingEntries(array $pool): array
    {
        $slugs = array_column($pool, 'slug');

        return array_values(array_filter(
            MockCropCatalog::entries(),
            fn (array $entry): bool => ! in_array($entry['slug'], $slugs, true)
        ));
    }
}
