<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Marketplace\Models\CropPriceAlias;

/**
 * Maps DA Bantay Presyo variant slugs to base crops so generic lookups
 * ("rice", "potato") resolve to real DA data. DA publishes grades, not
 * base crops: commercial-*-milled (rice), white-potato, banana-lakatan.
 */
final class DaCropVocabulary
{
    private const RICE_PREFIX = 'commercial-';

    private const RICE_NFA_SLUG = 'nfa';

    private const RICE_SLUG = 'rice';

    /**
     * @var array<string, string>
     */
    private const EXPLICIT_MAP = [
        'white-potato' => 'potato',
        'red-onion' => 'onion',
        'red-onion-imported' => 'onion',
        'white-onion' => 'onion',
        'white-onion-imported' => 'onion',
        // Str::slug deletes spaceless parens: 'Garlic(Native)' -> 'garlicnative'.
        'garlicnative' => 'garlic',
        'garlicimported' => 'garlic',
    ];

    /**
     * Bases that exist in DA tables but have no alias target yet.
     *
     * @var list<string>
     */
    private const EXTRA_BASES = [
        'bell-pepper',
        'pechay',
        'lettuce',
        'habichuelas',
    ];

    public static function canonicalSlug(string $slug): string
    {
        if ($slug === self::RICE_NFA_SLUG || str_starts_with($slug, self::RICE_PREFIX)) {
            return self::RICE_SLUG;
        }

        if (isset(self::EXPLICIT_MAP[$slug])) {
            return self::EXPLICIT_MAP[$slug];
        }

        foreach (self::knownBases() as $base) {
            if ($slug !== $base && str_starts_with($slug, $base.'-')) {
                return $base;
            }
        }

        return $slug;
    }

    /**
     * @return list<string> longest first so bell-pepper wins over bell.
     */
    private static function knownBases(): array
    {
        /** @var list<string> $targets */
        $targets = CropPriceAlias::distinct()->pluck('crop_slug')->all();

        $bases = array_unique(array_merge($targets, self::EXTRA_BASES));
        usort($bases, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $bases;
    }
}
