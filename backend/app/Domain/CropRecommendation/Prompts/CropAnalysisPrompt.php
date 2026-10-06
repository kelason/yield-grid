<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Prompts;

use App\Domain\CropRecommendation\Rotation\RotationRules;
use Domain\Farming\Models\Plot;

/**
 * Builds the crop-analysis LLM prompt. Plain PHP string building only —
 * Blade templates are banned project-wide, including for prompts.
 */
final class CropAnalysisPrompt
{
    /**
     * @param  array<string, mixed>  $agroData
     * @param  array{city?: string, state?: string, country?: string}  $location
     * @param  array{subtypes?: list<string>, irrigation?: string, goal?: string, season?: string, date?: string, previous_crops?: list<string>, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}  $context
     */
    public static function render(Plot $plot, array $agroData, array $location, array $context = []): string
    {
        $area = (float) ($plot->calculated_area ?? 1.0);
        $soil = $plot->soil_type instanceof \BackedEnum ? $plot->soil_type->value : (string) ($plot->soil_type ?? 'general');
        $fullLocation = self::fullLocation($location);

        return self::introBlock($plot, $agroData, $area, $soil, $fullLocation)
            .self::requirementsBlock($area, $soil, $fullLocation, $context);
    }

    /**
     * @param  array{city?: string, state?: string, country?: string}  $location
     */
    private static function fullLocation(array $location): string
    {
        $city = $location['city'] ?? 'Local Region';
        $state = $location['state'] ?? '';
        $country = $location['country'] ?? 'Philippines';

        return implode(', ', array_filter([$city, $state, $country]));
    }

    /**
     * @param  array<string, mixed>  $agroData
     */
    private static function introBlock(Plot $plot, array $agroData, float $area, string $soil, string $fullLocation): string
    {
        $weatherJson = json_encode($agroData['weather'] ?? []);
        $soilJson = json_encode($agroData['soil'] ?? []);

        $intro = <<<PROMPT
            You are an expert agricultural agronomist and crop advisor.
            Analyze the following specific farm plot:
            - Plot Name: {$plot->name}
            - Plot Size: {$area} hectares
            - Soil Type: {$soil}
            - Soil Conditions (temperature in Kelvin, moisture m3/m3): {$soilJson}
            - Local Weather Conditions: {$weatherJson}
            - Geographic Location: {$fullLocation}
            PROMPT;

        return $intro."\n\n";
    }

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, goal?: string, season?: string, date?: string, previous_crops?: list<string>, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}  $context
     */
    private static function requirementsBlock(float $area, string $soil, string $fullLocation, array $context): string
    {
        $contextBlock = self::contextBlock($context);
        $schemaKeys = self::schemaKeys($area, $soil, $fullLocation);

        return <<<PROMPT
            CRITICAL STRICT REQUIREMENTS:
            1. LOCATION RESTRICTION: You MUST ONLY recommend crops that are actively grown, native, commercially cultivated, and locally available in {$fullLocation}.
            2. PLOT SIZE SCALING ({$area} HECTARES): Tailor recommendations specifically for a plot of {$area} hectares. Small plots (<1 ha) should focus on high-yield intensive horticulture/vegetables, while medium (1-5 ha) and large plots (>5 ha) should focus on suitable commercial staples and field crops.
            3. YIELD CALCULATION: In "projected_yield", calculate the realistic total production for this entire {$area} hectare plot, as well as the per-hectare rate (e.g. "X tons total (Y tons/ha on {$area} ha)").
            4. SOIL COMPATIBILITY: Reflect why the crop excels in {$soil} soil.
            {$contextBlock}
            Output strictly as a JSON array of 10 objects. Do not include markdown formatting, code fences, or backticks.
            Each object must have the following keys:
            {$schemaKeys}
            PROMPT;
    }

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, goal?: string, season?: string, date?: string, previous_crops?: list<string>, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}  $context
     */
    private static function contextBlock(array $context): string
    {
        if ($context === []) {
            return '';
        }

        $lines = array_merge(
            self::preferenceLines($context),
            self::signalLines($context),
            [RotationRules::promptSection()]
        );

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, goal?: string, season?: string, date?: string, previous_crops?: list<string>, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}  $context
     * @return list<string>
     */
    private static function preferenceLines(array $context): array
    {
        $lines = [];

        if (isset($context['date'], $context['season'])) {
            $lines[] = "- ANALYSIS DATE: {$context['date']} ({$context['season']} season). Favor crops suited to this season.";
        }

        if (isset($context['subtypes']) && $context['subtypes'] !== []) {
            $list = implode(', ', $context['subtypes']);
            $lines[] = "- CROP TYPE PREFERENCE: recommend ONLY crops in these subtypes: {$list}.";
        }

        if (isset($context['irrigation'])) {
            $lines[] = '- IRRIGATION: '.self::irrigationDirective($context['irrigation']);
        }

        if (isset($context['goal'])) {
            $lines[] = '- GOAL: '.self::goalDirective($context['goal']);
        }

        return $lines;
    }

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, goal?: string, season?: string, date?: string, previous_crops?: list<string>, demands?: list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>}  $context
     * @return list<string>
     */
    private static function signalLines(array $context): array
    {
        $lines = [];

        if (isset($context['demands']) && $context['demands'] !== []) {
            $lines[] = '- MARKET SIGNALS (open buyer demand): '.self::demandLines($context['demands']).' Prefer demanded crops when agronomically suitable.';
        }

        if (isset($context['previous_crops']) && $context['previous_crops'] !== []) {
            $history = implode(', ', $context['previous_crops']);
            $lines[] = "- PLOT HISTORY (recent crops): {$history}. Avoid repeating the same family back to back; favor restorative follow-ups.";
        }

        return $lines;
    }

    private static function irrigationDirective(string $level): string
    {
        return match ($level) {
            'none' => 'no irrigation, rainfed only (none) — strongly prefer drought-tolerant crops.',
            'limited' => 'limited irrigation (limited) — prefer low-to-moderate water crops.',
            default => 'reliable irrigation (reliable) — water requirement is not a constraint.',
        };
    }

    private static function goalDirective(string $goal): string
    {
        return match ($goal) {
            'quick_cash' => 'quick cash turnover (quick_cash) — prefer short-cycle crops.',
            'food_security' => 'household food security (food_security) — prefer staples and continuous-harvest crops.',
            default => 'maximum profit (max_profit) — weight market value and yield.',
        };
    }

    /**
     * @param  list<array{crop: string, quantity_kg: mixed, target_price_per_kg: mixed, needed_by: mixed}>  $demands
     */
    private static function demandLines(array $demands): string
    {
        $parts = [];

        foreach ($demands as $demand) {
            $line = "{$demand['crop']} — {$demand['quantity_kg']} kg @ {$demand['target_price_per_kg']}/kg";

            if (! empty($demand['needed_by'])) {
                $line .= " needed by {$demand['needed_by']}";
            }

            $parts[] = $line;
        }

        return implode('; ', $parts).'.';
    }

    private static function schemaKeys(float $area, string $soil, string $fullLocation): string
    {
        return <<<KEYS
            - "crop_name": string
            - "confidence_score": integer between 0 and 100
            - "reasoning": string (explaining specific fit for {$area} hectares, {$soil} soil, and {$fullLocation})
            - "projected_yield": string (total yield on {$area} ha + per-hectare rate)
            - "produce_type": string (vegetable, fruit, or field_crop)
            - "subtype": string (e.g. citrus, fruiting, staple)
            KEYS;
    }
}
