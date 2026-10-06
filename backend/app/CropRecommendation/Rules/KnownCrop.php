<?php

declare(strict_types=1);

namespace App\CropRecommendation\Rules;

use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class KnownCrop implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || CropTaxonomy::matchSlug($value) === null) {
            $fail('The selected :attribute is not a known crop.');
        }
    }
}
