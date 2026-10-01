<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Prompts;

/**
 * Builds the crop-price estimation LLM prompt. Plain PHP string building
 * only — Blade templates are banned project-wide, including for prompts.
 */
final class CropPriceEstimatePrompt
{
    public static function render(string $input): string
    {
        return <<<PROMPT
            You estimate typical Philippine crop prices per kilogram in pesos (PHP) for a farmer marketplace price guide.

            ## Input crop
            {$input}

            ## Output format
            Respond with a JSON object only (no markdown fences) with exactly these keys:
            - is_crop: boolean, true only when the input is a recognizable crop, fruit, vegetable, or grain grown or sold in the Philippines (reject gibberish, numbers, and non-crop words)
            - display_name: the proper crop name (e.g. "Rice"), or null when is_crop is false
            - tiers: object with any of these optional numeric keys: farmgate, wholesale, retail (typical PHP per-kg prices; farmgate below wholesale below retail). Empty object when is_crop is false.

            Rules: base prices on typical recent Philippine market levels. Never invent precision you cannot support — round to whole pesos.
            PROMPT;
    }
}
