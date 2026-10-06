<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Actions;

use App\Domain\CropRecommendation\Enums\CompatibilityVerdict;
use App\Domain\CropRecommendation\Rotation\RotationRules;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

/**
 * Checks two crops for rotation and companion compatibility using
 * the curated family rules. Inputs must already be validated as
 * resolvable (see KnownCrop rule).
 */
final class CheckCompatibilityAction
{
    /**
     * @return array{crop_a: array{slug: string, name: string, family: string, type: string, subtype: string}, crop_b: array{slug: string, name: string, family: string, type: string, subtype: string}, verdict: string, rotation: array{verdict: string, reasons: list<string>}, companion: array{verdict: string, reasons: list<string>}}
     */
    public function execute(string $cropA, string $cropB): array
    {
        $a = $this->resolve($cropA);
        $b = $this->resolve($cropB);

        if ($a['slug'] === $b['slug']) {
            return $this->sameCrop($a);
        }

        $rotation = $this->rotationVerdict($a, $b);
        $companion = $this->companionVerdict($a, $b);

        return [
            'crop_a' => $a,
            'crop_b' => $b,
            'verdict' => $rotation['verdict'] === CompatibilityVerdict::AVOID->value
                || $companion['verdict'] === CompatibilityVerdict::AVOID->value
                ? CompatibilityVerdict::AVOID->value
                : $this->softerVerdict($rotation['verdict'], $companion['verdict']),
            'rotation' => $rotation,
            'companion' => $companion,
        ];
    }

    /**
     * @return array{slug: string, name: string, family: string, type: string, subtype: string}
     */
    private function resolve(string $input): array
    {
        $slug = CropTaxonomy::matchSlug($input) ?? throw new \LogicException("Unresolvable crop '{$input}'.");
        $profile = CropTaxonomy::profile($slug) ?? throw new \LogicException("Missing profile for '{$slug}'.");

        return [
            'slug' => $slug,
            'name' => $profile['name'],
            'family' => $profile['family'],
            'type' => $profile['type'],
            'subtype' => $profile['subtype'],
        ];
    }

    /**
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $crop
     * @return array{crop_a: array{slug: string, name: string, family: string, type: string, subtype: string}, crop_b: array{slug: string, name: string, family: string, type: string, subtype: string}, verdict: string, rotation: array{verdict: string, reasons: list<string>}, companion: array{verdict: string, reasons: list<string>}}
     */
    private function sameCrop(array $crop): array
    {
        $note = ['Same crop selected twice — nothing to compare.'];
        $result = $this->result(CompatibilityVerdict::COMPATIBLE, $note);

        return [
            'crop_a' => $crop,
            'crop_b' => $crop,
            'verdict' => CompatibilityVerdict::COMPATIBLE->value,
            'rotation' => $result,
            'companion' => $result,
        ];
    }

    /**
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $a
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $b
     * @return array{verdict: string, reasons: list<string>}
     */
    private function rotationVerdict(array $a, array $b): array
    {
        if ($a['family'] === $b['family']) {
            return $this->familyVerdict($a['family']);
        }

        $reasons = [];

        if ($this->restoresSoil($a, $b)) {
            $reasons[] = 'Good soil sequence: nitrogen-building legumes restore heavy feeders.';
        }

        if ($this->isFollowupPair($a, $b)) {
            $reasons[] = 'Recommended rotation pairing for these families.';
        }

        if ($reasons === []) {
            $reasons[] = 'No rotation conflict between these families.';
        }

        return $this->result(CompatibilityVerdict::COMPATIBLE, $reasons);
    }

    /**
     * @return array{verdict: string, reasons: list<string>}
     */
    private function familyVerdict(string $family): array
    {
        if (RotationRules::successionPenalty($family, $family) > 0) {
            return $this->result(CompatibilityVerdict::AVOID, [
                "Both are {$family} family — planting them back to back carries soil disease pressure.",
            ]);
        }

        return $this->result(CompatibilityVerdict::CAUTION, [
            "Both are {$family} family — rotate with an unrelated family when possible.",
        ]);
    }

    /**
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $a
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $b
     * @return array{verdict: string, reasons: list<string>}
     */
    private function companionVerdict(array $a, array $b): array
    {
        $clash = RotationRules::clashReason($a['slug'], $b['slug']);

        if ($clash !== null) {
            return $this->result(CompatibilityVerdict::AVOID, [$clash]);
        }

        if (in_array($b['family'], RotationRules::avoidCompanions($a['family']), true)
            || in_array($a['family'], RotationRules::avoidCompanions($b['family']), true)) {
            return $this->result(CompatibilityVerdict::AVOID, [
                "Keep {$a['family']} and {$b['family']} apart — they share pests or stunt each other.",
            ]);
        }

        return $this->result(CompatibilityVerdict::COMPATIBLE, ['No companion conflict between these families.']);
    }

    /**
     * @param  list<string>  $reasons
     * @return array{verdict: string, reasons: list<string>}
     */
    private function result(CompatibilityVerdict $verdict, array $reasons): array
    {
        return ['verdict' => $verdict->value, 'reasons' => $reasons];
    }

    private function softerVerdict(string $rotation, string $companion): string
    {
        if ($rotation === CompatibilityVerdict::CAUTION->value || $companion === CompatibilityVerdict::CAUTION->value) {
            return CompatibilityVerdict::CAUTION->value;
        }

        return CompatibilityVerdict::COMPATIBLE->value;
    }

    /**
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $a
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $b
     */
    private function restoresSoil(array $a, array $b): bool
    {
        $profileA = CropTaxonomy::profile($a['slug']);
        $profileB = CropTaxonomy::profile($b['slug']);

        return RotationRules::soilSequenceBonus($profileA['soil_need'] ?? null, $profileB['soil_need'] ?? null) > 0
            || RotationRules::soilSequenceBonus($profileB['soil_need'] ?? null, $profileA['soil_need'] ?? null) > 0;
    }

    /**
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $a
     * @param  array{slug: string, name: string, family: string, type: string, subtype: string}  $b
     */
    private function isFollowupPair(array $a, array $b): bool
    {
        return in_array($b['family'], RotationRules::goodFollowups($a['family']), true)
            || in_array($a['family'], RotationRules::goodFollowups($b['family']), true);
    }
}
