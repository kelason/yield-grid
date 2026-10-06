<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\DTOs;

use App\Domain\CropRecommendation\Enums\AnalysisGoal;
use App\Domain\CropRecommendation\Enums\IrrigationLevel;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

/**
 * Per-analysis farmer preferences. Unknown values are dropped so job
 * payloads from any source stay safe to consume.
 */
final class AnalysisPreferences
{
    /**
     * @param  list<string>  $produceTypes
     * @param  list<string>  $subtypes
     */
    public function __construct(
        public readonly array $produceTypes = [],
        public readonly array $subtypes = [],
        public readonly ?IrrigationLevel $irrigation = null,
        public readonly ?AnalysisGoal $goal = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $types = array_values(array_unique(array_intersect(
            (array) ($input['produce_types'] ?? []),
            CropTaxonomy::typeSlugs()
        )));
        $subtypes = array_values(array_unique(array_intersect(
            (array) ($input['subtypes'] ?? []),
            CropTaxonomy::subtypeSlugs()
        )));

        $irrigation = isset($input['irrigation']) && is_string($input['irrigation'])
            ? IrrigationLevel::tryFrom($input['irrigation'])
            : null;
        $goal = isset($input['goal']) && is_string($input['goal'])
            ? AnalysisGoal::tryFrom($input['goal'])
            : null;

        return new self($types, $subtypes, $irrigation, $goal);
    }

    /**
     * Subtypes the analysis must draw from. Explicit subtypes win; bare
     * types expand to all their subtypes; empty means no filter.
     *
     * @return list<string>
     */
    public function effectiveSubtypes(): array
    {
        if ($this->subtypes !== []) {
            return $this->subtypes;
        }

        $expanded = [];

        foreach (CropTaxonomy::subtypes() as $slug => $subtype) {
            if (in_array($subtype['type'], $this->produceTypes, true)) {
                $expanded[] = $slug;
            }
        }

        return $expanded;
    }

    public function isEmpty(): bool
    {
        return $this->produceTypes === []
            && $this->subtypes === []
            && $this->irrigation === null
            && $this->goal === null;
    }

    /**
     * @return array{produce_types: list<string>, subtypes: list<string>, irrigation: ?string, goal: ?string}
     */
    public function toArray(): array
    {
        return [
            'produce_types' => $this->produceTypes,
            'subtypes' => $this->subtypes,
            'irrigation' => $this->irrigation?->value,
            'goal' => $this->goal?->value,
        ];
    }
}
