<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Prompts;

/**
 * Builds the crop-name normalization LLM prompt. Plain PHP string building
 * only — Blade templates are banned project-wide, including for prompts.
 */
final class CropNamePrompt
{
    /**
     * @param  list<string>  $knownSlugs
     */
    public static function render(string $input, array $knownSlugs): string
    {
        $known = implode(', ', $knownSlugs);

        return <<<PROMPT
            You normalize crop names typed by Filipino farmers and buyers. Match the input to exactly one crop from the known list, correcting misspellings and translating Filipino/regional names to the closest known crop (e.g. "palay" -> rice, "kamatis" -> tomato, "rcie" -> rice).

            ## Input
            {$input}

            ## Known crops (slugs)
            {$known}

            ## Output format
            Respond with a JSON object only (no markdown fences) with exactly these keys:
            - match: the matched slug from the known list, or null when the input is not a recognizable crop name (gibberish, numbers, non-crop words)
            - confidence: integer 0-100 for the match (0 when match is null)

            Rules: never invent a slug outside the known list. Only match when the input names the SAME crop (translation or clear misspelling) — similar names of different crops (potato vs tomato, rice vs maize) must return null. Be strict: only confident matches score 70 or above.
            PROMPT;
    }
}
