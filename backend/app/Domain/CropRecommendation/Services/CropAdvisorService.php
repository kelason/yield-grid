<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Services;

use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CropAdvisorService
{
    private string $apiKey;

    private int $cacheTtlSeconds = 1800; // 30 minutes recommendation cache

    private int $rateLimitRpm = 10;      // Max 10 requests per minute for Gemini API

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?? '');
    }

    public function getRecommendations(Plot $plot, array $agroData): array
    {
        $location = $plot->resolveLocation();
        $prompt = $this->buildPrompt($plot, $agroData, $location);

        // 1. Check AI recommendation cache first to avoid re-querying Gemini for identical plot inputs
        $cacheKey = 'gemini_crop_rec_'.md5($plot->id.'_'.$prompt);
        $cachedRecs = Cache::get($cacheKey);
        if (is_array($cachedRecs) && count($cachedRecs) > 0) {
            Log::info("Serving cached crop recommendations for plot {$plot->id}.");

            return $cachedRecs;
        }

        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is missing. Using location-tailored mock recommendations.');

            return $this->getMockRecommendations($plot, $location);
        }

        // 2. Check Gemini rate limiter
        $rateKey = 'gemini_api_rate_limit';
        if (RateLimiter::tooManyAttempts($rateKey, $this->rateLimitRpm)) {
            $seconds = RateLimiter::availableIn($rateKey);
            Log::warning("Gemini API rate limit reached ({$this->rateLimitRpm} RPM). Backing off for {$seconds}s. Serving localized recommendations.");

            return $this->getMockRecommendations($plot, $location);
        }

        RateLimiter::hit($rateKey, 60);

        try {
            $response = Http::timeout(12)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key='.$this->apiKey, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

                $decoded = json_decode($text, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    // Cache the successful Gemini output for 30 minutes
                    Cache::put($cacheKey, $decoded, $this->cacheTtlSeconds);

                    return $decoded;
                }
            }

            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Gemini Request Exception', ['message' => $e->getMessage()]);
        }

        return $this->getMockRecommendations($plot, $location);
    }

    private function buildPrompt(Plot $plot, array $agroData, array $location): string
    {
        $weatherJson = json_encode($agroData['weather'] ?? []);
        $soilJson = json_encode($agroData['soil'] ?? []);
        $area = (float) ($plot->calculated_area ?? 1.0);
        $soil = $plot->soil_type instanceof \BackedEnum ? $plot->soil_type->value : (string) ($plot->soil_type ?? 'general');
        $city = $location['city'] ?? 'Local Region';
        $state = $location['state'] ?? '';
        $country = $location['country'] ?? 'Philippines';
        $fullLocation = implode(', ', array_filter([$city, $state, $country]));

        return <<<PROMPT
You are an expert agricultural agronomist and crop advisor.
Analyze the following specific farm plot:
- Plot Name: {$plot->name}
- Plot Size: {$area} hectares
- Soil Type: {$soil}
- Soil Conditions (temperature in Kelvin, moisture m3/m3): {$soilJson}
- Local Weather Conditions: {$weatherJson}
- Geographic Location: {$fullLocation}

CRITICAL STRICT REQUIREMENTS:
1. LOCATION RESTRICTION: You MUST ONLY recommend crops that are actively grown, native, commercially cultivated, and locally available in {$fullLocation}.
2. PLOT SIZE SCALING ({$area} HECTARES): Tailor recommendations specifically for a plot of {$area} hectares. Small plots (<1 ha) should focus on high-yield intensive horticulture/vegetables, while medium (1-5 ha) and large plots (>5 ha) should focus on suitable commercial staples and field crops.
3. YIELD CALCULATION: In "projected_yield", calculate the realistic total production for this entire {$area} hectare plot, as well as the per-hectare rate (e.g. "X tons total (Y tons/ha on {$area} ha)").
4. SOIL COMPATIBILITY: Reflect why the crop excels in {$soil} soil.

Output strictly as a JSON array of 10 objects. Do not include markdown formatting, code fences, or backticks.
Each object must have the following keys:
- "crop_name": string
- "confidence_score": integer between 0 and 100
- "reasoning": string (explaining specific fit for {$area} hectares, {$soil} soil, and {$fullLocation})
- "projected_yield": string (total yield on {$area} ha + per-hectare rate)
PROMPT;
    }

    private function getMockRecommendations(Plot $plot, array $location): array
    {
        $city = $location['city'] ?? 'Local Region';
        $state = $location['state'] ?? '';
        $locDisplay = trim($city.($state && stripos($city, $state) === false ? ", $state" : ''));
        $locLower = strtolower($locDisplay.' '.($location['country'] ?? 'philippines'));
        $area = max(0.01, (float) ($plot->calculated_area ?? 1.0));
        $areaText = number_format($area, 2);
        $soil = strtolower($plot->soil_type instanceof \BackedEnum ? $plot->soil_type->value : (string) ($plot->soil_type ?? 'loamy'));

        // Comprehensive Agricultural Crop Matrix
        $crops = [
            [
                'name' => 'Sweet Potato (Camote)',
                'ideal_soils' => ['sandy', 'loamy', 'peat'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['tarlac', 'pampanga', 'central luzon', 'bicol'],
                'min_area' => 0.05, 'max_area' => 10.0,
                'yield_ha' => 14.5, 'unit' => 'tons',
                'confidence' => 93,
                'soil_reason' => 'Loose :soil soil prevents subterranean compaction, enabling tubers to expand smoothly without root deformities or rot.',
                'loc_reason' => 'Capitalizes on high commercial demand and regional root-crop processing hubs in :loc.',
            ],
            [
                'name' => 'Peanuts (Mani)',
                'ideal_soils' => ['sandy', 'loamy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['tarlac', 'pangasinan', 'ilocos', 'central luzon'],
                'min_area' => 0.05, 'max_area' => 5.0,
                'yield_ha' => 2.8, 'unit' => 'tons',
                'confidence' => 89,
                'soil_reason' => 'Porous, aerated :soil soil allows flower pegs to easily penetrate beneath the surface to develop pods while fixing vital soil nitrogen.',
                'loc_reason' => 'Strong local wholesale and food processor buying networks in :loc.',
            ],
            [
                'name' => 'Watermelon (Pakwan)',
                'ideal_soils' => ['sandy', 'silt', 'loamy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['tarlac', 'pampanga', 'pangasinan', 'central luzon'],
                'min_area' => 0.1, 'max_area' => 6.0,
                'yield_ha' => 26.0, 'unit' => 'tons',
                'confidence' => 88,
                'soil_reason' => 'Warm, quick-draining :soil soil promotes high sugar content (Brix) and prevents fungal root-rot during fruit set.',
                'loc_reason' => 'High summer cash turnover and strong demand across wholesale markets in :loc.',
            ],
            [
                'name' => 'Cassava (Kamoteng Kahoy)',
                'ideal_soils' => ['sandy', 'peat', 'loamy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['tarlac', 'mindanao', 'visayas', 'central luzon'],
                'min_area' => 0.2, 'max_area' => 20.0,
                'yield_ha' => 22.0, 'unit' => 'tons',
                'confidence' => 85,
                'soil_reason' => 'Drought-hardy starch crop that thrives with minimal inputs in loose :soil ground with effortless harvest pulling.',
                'loc_reason' => 'Steady industrial feed and starch mill demand in :loc.',
            ],
            [
                'name' => 'Lowland Rice (Palay)',
                'ideal_soils' => ['clay', 'silt'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['nueva ecija', 'tarlac', 'central luzon', 'pangasinan', 'isabela'],
                'min_area' => 0.1, 'max_area' => 50.0,
                'yield_ha' => 5.8, 'unit' => 'tons',
                'confidence' => 96,
                'soil_reason' => 'Heavy :soil soil creates an impermeable hardpan that retains standing water efficiently, drastically reducing pumping and irrigation expenses.',
                'loc_reason' => 'Direct access to major rice trading stations and drying mills across :loc.',
            ],
            [
                'name' => 'Sugarcane (Tubo)',
                'ideal_soils' => ['clay', 'loamy'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['tarlac', 'pampanga', 'negros', 'batangas'],
                'min_area' => 0.5, 'max_area' => 100.0,
                'yield_ha' => 72.0, 'unit' => 'tons',
                'confidence' => 91,
                'soil_reason' => 'Dense :soil soil anchors heavy cane stalks securely against typhoon winds while maintaining continuous moisture for high sugar synthesis.',
                'loc_reason' => 'Strategic proximity to regional sugar centrals and milling facilities in :loc.',
            ],
            [
                'name' => 'Taro (Gabi)',
                'ideal_soils' => ['clay', 'peat', 'silt'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['tarlac', 'bicol', 'quezon', 'central luzon'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 13.0, 'unit' => 'tons',
                'confidence' => 87,
                'soil_reason' => 'Semi-aquatic root crop that thrives in wet, heavy :soil soil where standard vegetables would suffocate.',
                'loc_reason' => 'High regional culinary demand for both leaves and corms in :loc.',
            ],
            [
                'name' => 'Red Creole Onions (Sibuyas)',
                'ideal_soils' => ['silt', 'loamy', 'sandy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['nueva ecija', 'pangasinan', 'central luzon'],
                'min_area' => 0.05, 'max_area' => 4.0,
                'yield_ha' => 16.5, 'unit' => 'tons',
                'confidence' => 95,
                'soil_reason' => 'Fine, non-crusting :soil soil allows onion bulbs to expand symmetrically without constriction.',
                'loc_reason' => 'Direct proximity to Bongabon/Nueva Ecija cold-storage and national wholesale dispatch in :loc.',
            ],
            [
                'name' => 'Tomatoes (Kamatis)',
                'ideal_soils' => ['loamy', 'silt'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['central luzon', 'ilocos', 'nueva ecija', 'bulacan'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 22.0, 'unit' => 'tons',
                'confidence' => 90,
                'soil_reason' => 'Balanced :soil soil provides optimal drainage and steady capillary water, suppressing bacterial wilt.',
                'loc_reason' => 'High daily turnover and strong sales through local fresh wet markets in :loc.',
            ],
            [
                'name' => 'Eggplant (Talong)',
                'ideal_soils' => ['loamy', 'silt', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['pangasinan', 'nueva ecija', 'central luzon'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 19.0, 'unit' => 'tons',
                'confidence' => 89,
                'soil_reason' => 'Resilient taproot system thrives in nutrient-dense :soil soil, delivering continuous weekly flushes.',
                'loc_reason' => 'Year-round dietary staple with stable farm-gate prices in :loc.',
            ],
            [
                'name' => 'Bitter Gourd (Ampalaya)',
                'ideal_soils' => ['loamy', 'silt'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['pampanga', 'bulacan', 'nueva ecija', 'central luzon'],
                'min_area' => 0.05, 'max_area' => 2.0,
                'yield_ha' => 15.0, 'unit' => 'tons',
                'confidence' => 88,
                'soil_reason' => 'High organic aeration in :soil soil supports rapid vine climbing and disease-free flowering.',
                'loc_reason' => 'Premium-priced vegetable favored by commercial traders across :loc.',
            ],
            [
                'name' => 'Yellow Corn (Maize)',
                'ideal_soils' => ['loamy', 'clay', 'silt'],
                'unsuitable_soils' => [],
                'regions' => ['isabela', 'tarlac', 'nueva ecija', 'pangasinan', 'central luzon'],
                'min_area' => 0.3, 'max_area' => 20.0,
                'yield_ha' => 5.2, 'unit' => 'tons',
                'confidence' => 88,
                'soil_reason' => 'Sturdy root architecture draws deep nutrients from fertile :soil soil.',
                'loc_reason' => 'Surging feed-mill demand from livestock and poultry operators in :loc.',
            ],
            [
                'name' => 'Hot Chili (Siling Labuyo)',
                'ideal_soils' => ['peat', 'loamy', 'sandy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['bicol', 'central luzon', 'tarlac', 'batangas'],
                'min_area' => 0.02, 'max_area' => 1.5,
                'yield_ha' => 8.5, 'unit' => 'tons',
                'confidence' => 86,
                'soil_reason' => 'Warm, organic-rich :soil soil stimulates concentrated capsaicin synthesis and continuous pod set.',
                'loc_reason' => 'Outstanding value per square meter, offering high income density on compact plots in :loc.',
            ],
            [
                'name' => 'Highland Strawberries',
                'ideal_soils' => ['loamy', 'peat'],
                'unsuitable_soils' => ['clay', 'chalky'],
                'regions' => ['benguet', 'baguio', 'cordillera'],
                'min_area' => 0.02, 'max_area' => 2.0,
                'yield_ha' => 24.0, 'unit' => 'tons',
                'confidence' => 97,
                'soil_reason' => 'Acidic, humus-rich :soil soil fuels continuous sweet berry flowering and root aeration.',
                'loc_reason' => 'Famous high-altitude agro-climate and agritourism market in :loc.',
            ],
            [
                'name' => 'Highland Cabbage (Repolyo)',
                'ideal_soils' => ['loamy', 'clay', 'silt'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['benguet', 'baguio', 'bukidnon'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 28.0, 'unit' => 'tons',
                'confidence' => 92,
                'soil_reason' => 'Moisture-retaining :soil soil produces dense, compact heads without inner leaf tip-burn.',
                'loc_reason' => 'Core production zone supplying major metropolitan trading centers from :loc.',
            ],
            [
                'name' => 'Pineapple (Pinya)',
                'ideal_soils' => ['peat', 'sandy', 'loamy'],
                'unsuitable_soils' => ['clay', 'chalky'],
                'regions' => ['cavite', 'laguna', 'bukidnon', 'camarines'],
                'min_area' => 0.2, 'max_area' => 15.0,
                'yield_ha' => 36.0, 'unit' => 'tons',
                'confidence' => 91,
                'soil_reason' => 'Prefers acidic, free-draining :soil soil, producing high-sugar, sun-ripened fruit crowns.',
                'loc_reason' => 'Flourishes in the well-drained volcanic/sub-tropical conditions of :loc.',
            ],
            [
                'name' => 'Sunflowers (Commercial Sunflower)',
                'ideal_soils' => ['chalky', 'loamy', 'sandy'],
                'unsuitable_soils' => ['peat'],
                'regions' => ['central luzon', 'nueva ecija', 'tarlac'],
                'min_area' => 0.2, 'max_area' => 10.0,
                'yield_ha' => 3.2, 'unit' => 'tons',
                'confidence' => 86,
                'soil_reason' => 'Deep taproot extracts calcium and micronutrients from alkaline :soil soil with superior drought resilience.',
                'loc_reason' => 'Dual revenue potential from seed harvest and popular local agritourism in :loc.',
            ],
            [
                'name' => 'Grain Sorghum',
                'ideal_soils' => ['chalky', 'sandy', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['central luzon', 'mindanao', 'ilocos'],
                'min_area' => 0.5, 'max_area' => 20.0,
                'yield_ha' => 4.6, 'unit' => 'tons',
                'confidence' => 84,
                'soil_reason' => 'Tolerates alkaline pH and mineral-heavy :soil ground where sensitive vegetables struggle.',
                'loc_reason' => 'Reliable climate-resilient feed grain with steady commercial off-takers in :loc.',
            ],
            [
                'name' => 'Commercial Coconut Grove',
                'ideal_soils' => ['sandy', 'loamy', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['quezon', 'davao', 'bicol', 'mindanao', 'visayas'],
                'min_area' => 2.0, 'max_area' => 100.0,
                'yield_ha' => 9500, 'unit' => 'nuts',
                'confidence' => 91,
                'soil_reason' => 'Deep-reaching root system anchors effortlessly in :soil terrain, sustaining multi-decade nut yields.',
                'loc_reason' => 'Proximity to copra processing facilities and coconut oil extraction plants across :loc.',
            ],
            [
                'name' => 'Mungbean (Munggo / Balatong)',
                'ideal_soils' => ['sandy', 'loamy', 'silt'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['tarlac', 'pangasinan', 'central luzon', 'ilocos'],
                'min_area' => 0.1, 'max_area' => 10.0,
                'yield_ha' => 1.4, 'unit' => 'tons',
                'confidence' => 90,
                'soil_reason' => 'Short-duration legume that enriches :soil soil with fixed atmospheric nitrogen between primary rotations.',
                'loc_reason' => 'High staple consumer demand and active wholesale consolidation hubs across :loc.',
            ],
            [
                'name' => 'String Beans (Sitaw)',
                'ideal_soils' => ['loamy', 'silt', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['central luzon', 'tarlac', 'nueva ecija', 'pampanga'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 12.5, 'unit' => 'tons',
                'confidence' => 89,
                'soil_reason' => 'Rapid vine development in fertile :soil ground with high continuous pod yields on trellises.',
                'loc_reason' => 'Consistent daily turnover through regional trading posts and public markets in :loc.',
            ],
            [
                'name' => 'Okra (Lady\'s Finger)',
                'ideal_soils' => ['loamy', 'clay', 'sandy'],
                'unsuitable_soils' => [],
                'regions' => ['central luzon', 'tarlac', 'pampanga', 'nueva ecija'],
                'min_area' => 0.05, 'max_area' => 4.0,
                'yield_ha' => 10.0, 'unit' => 'tons',
                'confidence' => 88,
                'soil_reason' => 'Hardy taproot tolerates both wet and dry cycles in :soil ground while continuously bearing tender pods.',
                'loc_reason' => 'Growing export and domestic fresh processing linkages operating in :loc.',
            ],
            [
                'name' => 'Squash (Kalabasa)',
                'ideal_soils' => ['loamy', 'silt', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['central luzon', 'nueva ecija', 'tarlac', 'pangasinan'],
                'min_area' => 0.1, 'max_area' => 5.0,
                'yield_ha' => 20.0, 'unit' => 'tons',
                'confidence' => 87,
                'soil_reason' => 'Wide trailing canopy shades :soil soil, suppressing weed growth and conserving root-zone moisture.',
                'loc_reason' => 'Long post-harvest shelf life providing price stability during transport across :loc.',
            ],
            [
                'name' => 'Native Ginger (Luya)',
                'ideal_soils' => ['loamy', 'sandy', 'peat'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['central luzon', 'batangas', 'quezon', 'bicol'],
                'min_area' => 0.02, 'max_area' => 2.0,
                'yield_ha' => 18.0, 'unit' => 'tons',
                'confidence' => 86,
                'soil_reason' => 'Friable, organic-rich :soil soil prevents rhizome rot and enables massive underground cluster expansion.',
                'loc_reason' => 'Lucrative cash-density per square meter in local culinary and herbal markets across :loc.',
            ],
            [
                'name' => 'Garlic (Bawang)',
                'ideal_soils' => ['sandy', 'silt', 'loamy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['ilocos', 'pangasinan', 'central luzon', 'tarlac'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 5.5, 'unit' => 'tons',
                'confidence' => 85,
                'soil_reason' => 'Well-draining, non-crusting :soil soil facilitates uniform bulb cloves without fungal damping.',
                'loc_reason' => 'High-value commodity enjoying premium local market pricing across :loc.',
            ],
            [
                'name' => 'Cucumber (Pipino)',
                'ideal_soils' => ['loamy', 'silt', 'sandy'],
                'unsuitable_soils' => ['clay'],
                'regions' => ['central luzon', 'nueva ecija', 'pampanga', 'tarlac'],
                'min_area' => 0.05, 'max_area' => 3.0,
                'yield_ha' => 25.0, 'unit' => 'tons',
                'confidence' => 87,
                'soil_reason' => 'Fast-rooting crop that extracts balanced moisture from aerated :soil beds without root stagnation.',
                'loc_reason' => 'Short 45-day turnaround delivering quick seasonal cash flow to farmers in :loc.',
            ],
            [
                'name' => 'Commercial Malunggay (Moringa)',
                'ideal_soils' => ['sandy', 'loamy', 'chalky'],
                'unsuitable_soils' => ['clay', 'peat'],
                'regions' => ['central luzon', 'pangasinan', 'ilocos', 'tarlac'],
                'min_area' => 0.1, 'max_area' => 10.0,
                'yield_ha' => 12.0, 'unit' => 'tons',
                'confidence' => 86,
                'soil_reason' => 'Extremely drought-tolerant deep taproot thrives even in poor, well-draining :soil soils.',
                'loc_reason' => 'Surging nutraceutical powder and food-grade processor demand across :loc.',
            ],
        ];

        // Score each crop dynamically based on Soil Compatibility + Regional Fit + Plot Area Suitability
        $scored = [];
        foreach ($crops as $crop) {
            $score = 50;

            // 1. Soil Compatibility Score
            if (in_array($soil, $crop['ideal_soils'])) {
                $score += 40;
            } elseif (in_array($soil, $crop['unsuitable_soils'])) {
                $score -= 60; // Strong penalty for agronomic mismatch
            } else {
                $score += 15; // Tolerated
            }

            // 2. Geographic / Regional Fit Score
            $regionMatched = false;
            foreach ($crop['regions'] as $reg) {
                if (str_contains($locLower, $reg)) {
                    $score += 35;
                    $regionMatched = true;
                    break;
                }
            }
            if (! $regionMatched && in_array('central luzon', $crop['regions']) && (str_contains($locLower, 'tarlac') || str_contains($locLower, 'nueva ecija') || str_contains($locLower, 'pampanga') || str_contains($locLower, 'bulacan'))) {
                $score += 25;
            }

            // 3. Area Suitability Score
            if ($area >= $crop['min_area'] && $area <= $crop['max_area']) {
                $score += 15;
            } elseif ($area < $crop['min_area']) {
                $score -= 20; // Needs larger scale
            }

            $scored[] = [
                'crop' => $crop,
                'score' => $score,
            ];
        }

        // Sort descending by highest score
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Select top 10 distinct crops
        $top10 = array_slice($scored, 0, 10);

        $recommendations = [];
        foreach ($top10 as $item) {
            $crop = $item['crop'];
            $calculatedYield = round($area * $crop['yield_ha'], 2);
            $unit = $crop['unit'];
            $yieldStr = number_format($calculatedYield, 2)." {$unit} total (".number_format($crop['yield_ha'], 1)." {$unit}/ha on {$areaText} ha)";
            if ($unit === 'nuts') {
                $yieldStr = number_format($calculatedYield).' nuts/yr ('.$areaText.' ha grove)';
            }

            $soilReason = str_replace(':soil', $soil, $crop['soil_reason']);
            $locReason = str_replace(':loc', $locDisplay ?: 'your area', $crop['loc_reason']);
            $reasoning = "{$soilReason} {$locReason} Perfectly scaled for {$areaText} hectares.";

            $recommendations[] = [
                'crop_name' => $crop['name'],
                'confidence_score' => min(98, max(75, $crop['confidence'] + rand(-2, 2))),
                'reasoning' => $reasoning,
                'projected_yield' => $yieldStr,
            ];
        }

        return $recommendations;
    }
}
