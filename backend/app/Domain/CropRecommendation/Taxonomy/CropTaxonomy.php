<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Taxonomy;

use Illuminate\Support\Str;

/**
 * Produce taxonomy: the canonical produce_type → subtype → crop vocabulary
 * shared by the preference UI, the AI prompt, and the mock catalog.
 */
final class CropTaxonomy
{
    /**
     * @return array<string, string> slug => label, in display order.
     */
    public static function types(): array
    {
        return [
            'vegetable' => 'Vegetables',
            'fruit' => 'Fruits',
            'field_crop' => 'Field Crops',
        ];
    }

    /**
     * @return list<string>
     */
    public static function typeSlugs(): array
    {
        return array_keys(self::types());
    }

    /**
     * @return array<string, array{label: string, type: string}>
     */
    public static function subtypes(): array
    {
        return [
            'leafy_greens' => ['label' => 'Leafy Greens', 'type' => 'vegetable'],
            'cruciferous' => ['label' => 'Cruciferous', 'type' => 'vegetable'],
            'fruiting' => ['label' => 'Fruiting Vegetables', 'type' => 'vegetable'],
            'root_tuber' => ['label' => 'Root & Tuber', 'type' => 'vegetable'],
            'bulb_stem' => ['label' => 'Bulb & Stem', 'type' => 'vegetable'],
            'legume_pod' => ['label' => 'Legumes & Pods', 'type' => 'vegetable'],
            'herbs' => ['label' => 'Herbs & Spices', 'type' => 'vegetable'],
            'tropical_tree' => ['label' => 'Tropical & Tree Fruits', 'type' => 'fruit'],
            'citrus' => ['label' => 'Citrus', 'type' => 'fruit'],
            'vine_ground' => ['label' => 'Vine & Ground Fruits', 'type' => 'fruit'],
            'berry' => ['label' => 'Berries', 'type' => 'fruit'],
            'staple' => ['label' => 'Staple Crops', 'type' => 'field_crop'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function subtypeSlugs(): array
    {
        return array_keys(self::subtypes());
    }

    /**
     * @return array<string, array<string, mixed>> slug => profile with type merged in.
     */
    public static function crops(): array
    {
        return array_merge(
            self::withType(VegetableProfiles::all(), 'vegetable'),
            self::withType(FruitProfiles::all(), 'fruit'),
            self::withType(FieldCropProfiles::all(), 'field_crop'),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function profile(string $slug): ?array
    {
        return self::crops()[$slug] ?? null;
    }

    /**
     * Flat crop list for pickers, sorted by display name.
     *
     * @return list<array{slug: string, name: string, type: string, subtype: string}>
     */
    public static function cropList(): array
    {
        $list = [];

        foreach (self::crops() as $slug => $profile) {
            $list[] = [
                'slug' => $slug,
                'name' => $profile['name'],
                'type' => $profile['type'],
                'subtype' => $profile['subtype'],
            ];
        }

        usort($list, fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $list;
    }

    /**
     * @return list<string> crop slugs in the subtype.
     */
    public static function cropsForSubtype(string $subtype): array
    {
        $slugs = [];

        foreach (self::crops() as $slug => $profile) {
            if ($profile['subtype'] === $subtype) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * @return list<string> crop slugs in the produce type.
     */
    public static function cropsForType(string $type): array
    {
        $slugs = [];

        foreach (self::crops() as $slug => $profile) {
            if ($profile['type'] === $type) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * Nested selection tree for the preference UI.
     *
     * @return array<string, array{label: string, subtypes: array<string, array{label: string, crops: list<string>, crop_count: int}>}>
     */
    public static function tree(): array
    {
        $tree = [];

        foreach (self::types() as $typeSlug => $label) {
            $tree[$typeSlug] = ['label' => $label, 'subtypes' => []];
        }

        foreach (self::subtypes() as $subtypeSlug => $subtype) {
            $crops = self::cropsForSubtype($subtypeSlug);
            $tree[$subtype['type']]['subtypes'][$subtypeSlug] = [
                'label' => $subtype['label'],
                'crops' => $crops,
                'crop_count' => count($crops),
            ];
        }

        return $tree;
    }

    public static function seasonForMonth(int $month): string
    {
        return $month >= 6 && $month <= 11 ? 'wet' : 'dry';
    }

    public static function currentSeason(): string
    {
        return self::seasonForMonth((int) now()->month);
    }

    /**
     * Match free text (demand crop, contract crop) to a catalog slug.
     */
    public static function matchSlug(string $cropName): ?string
    {
        $forms = self::nameForms($cropName);

        return self::exactSlug($forms) ?? self::fuzzySlug($forms);
    }

    /**
     * @param  list<string>  $forms
     */
    private static function exactSlug(array $forms): ?string
    {
        foreach (self::crops() as $slug => $profile) {
            $targets = self::slugTargets($slug, $profile);

            foreach ($forms as $form) {
                if ($form !== '' && in_array($form, $targets, true)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $forms
     */
    private static function fuzzySlug(array $forms): ?string
    {
        foreach (self::crops() as $slug => $profile) {
            $targets = self::slugTargets($slug, $profile);

            foreach ($forms as $form) {
                if (strlen($form) < 4) {
                    continue;
                }

                foreach ($targets as $target) {
                    if (str_contains($target, $form) || str_contains($form, $target)) {
                        return $slug;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    private static function slugTargets(string $slug, array $profile): array
    {
        return [$slug, Str::slug($profile['name']), Str::slug(Str::before($profile['name'], '('))];
    }

    /**
     * @return list<string>
     */
    private static function nameForms(string $cropName): array
    {
        $full = Str::slug($cropName);
        $base = Str::slug(Str::before($cropName, '('));

        return array_values(array_unique([$full, $base, Str::singular($full), Str::singular($base)]));
    }

    /**
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, array<string, mixed>>
     */
    private static function withType(array $profiles, string $type): array
    {
        foreach ($profiles as $slug => $profile) {
            $profiles[$slug]['type'] = $type;
        }

        return $profiles;
    }
}
