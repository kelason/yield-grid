<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Actions;

use App\Domain\CropRecommendation\DTOs\AnalysisPreferences;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Plot;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gathers everything the AI needs beyond soil and weather: farmer
 * preferences, season, open market demand, and recent farm crops.
 */
final class BuildAnalysisContextAction implements BuildsAnalysisContext
{
    private const MAX_DEMANDS = 10;

    private const MAX_HISTORY_CROPS = 5;

    /**
     * @param  array<string, mixed>|null  $preferences
     * @return array{subtypes?: list<string>, irrigation?: string, goal?: string, season: string, date: string, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>, previous_crops?: list<string>}
     */
    public function execute(Plot $plot, ?array $preferences): array
    {
        $prefs = AnalysisPreferences::fromArray($preferences ?? []);

        return array_merge(
            [
                'season' => CropTaxonomy::currentSeason(),
                'date' => now()->toDateString(),
            ],
            $this->preferenceContext($prefs),
            $this->demandContext(),
            $this->historyContext($plot),
        );
    }

    /**
     * @return array{subtypes?: list<string>, irrigation?: string, goal?: string}
     */
    private function preferenceContext(AnalysisPreferences $prefs): array
    {
        $context = [];

        if ($prefs->effectiveSubtypes() !== []) {
            $context['subtypes'] = $prefs->effectiveSubtypes();
        }

        if ($prefs->irrigation !== null) {
            $context['irrigation'] = $prefs->irrigation->value;
        }

        if ($prefs->goal !== null) {
            $context['goal'] = $prefs->goal->value;
        }

        return $context;
    }

    /**
     * @return array{demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}
     */
    private function demandContext(): array
    {
        $demands = $this->openDemands();

        return $demands === [] ? [] : ['demands' => $demands];
    }

    /**
     * @return array{previous_crops?: list<string>}
     */
    private function historyContext(Plot $plot): array
    {
        $history = $this->recentCropSlugs($plot);

        return $history === [] ? [] : ['previous_crops' => $history];
    }

    /**
     * @return list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>
     */
    private function openDemands(): array
    {
        return CropDemand::query()
            ->where('status', DemandStatus::OPEN)
            ->visible()
            ->orderByDesc('total_budget')
            ->limit(self::MAX_DEMANDS)
            ->get(['crop_name', 'remaining_quantity_kg', 'target_price_per_kg', 'needed_by_date'])
            ->map(fn (CropDemand $demand): array => [
                'crop' => $demand->crop_name,
                'quantity_kg' => $demand->remaining_quantity_kg,
                'target_price_per_kg' => $demand->target_price_per_kg,
                'needed_by' => $demand->needed_by_date,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function recentCropSlugs(Plot $plot): array
    {
        $names = $this->recentNames(
            CropRecommendation::query()->where('plot_id', $plot->id)->where('status', RecommendationStatus::ACCEPTED)
        );
        $farmerId = $plot->farm?->user_id;

        if ($farmerId !== null) {
            $names = array_merge(
                $this->recentNames(ForwardContract::query()->where('farmer_id', $farmerId)),
                $this->recentNames(HarvestListing::query()->where('farmer_id', $farmerId)),
                $names
            );
        }

        $slugs = [];

        foreach ($names as $name) {
            $slug = CropTaxonomy::matchSlug((string) $name);

            if ($slug !== null && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return array_slice($slugs, 0, self::MAX_HISTORY_CROPS);
    }

    /**
     * @param  Builder<CropRecommendation>|Builder<ForwardContract>|Builder<HarvestListing>  $query
     * @return list<string>
     */
    private function recentNames(Builder $query): array
    {
        return $query->latest()->limit(self::MAX_HISTORY_CROPS)->pluck('crop_name')->all();
    }
}
