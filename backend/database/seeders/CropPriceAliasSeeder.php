<?php

namespace Database\Seeders;

use App\Domain\Marketplace\Models\CropPriceAlias;
use Illuminate\Database\Seeder;

class CropPriceAliasSeeder extends Seeder
{
    /**
     * Filipino / regional / plural crop names mapped to canonical slugs.
     * Slugs resolve once reference rows exist (DA sync or --sample).
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'palay' => 'rice',
        'bigas' => 'rice',
        'mais' => 'corn',
        'kamatis' => 'tomato',
        'tomatoes' => 'tomato',
        'sibuyas' => 'onion',
        'onions' => 'onion',
        'bawang' => 'garlic',
        'luya' => 'ginger',
        'talong' => 'eggplant',
        'eggplants' => 'eggplant',
        'bitter-gourd' => 'ampalaya',
        'sili' => 'chili',
        'calamansi' => 'calamansi',
        'kalamansi' => 'calamansi',
        'sitaw' => 'sitao',
        'string-beans' => 'sitao',
        'okra' => 'okra',
        'repolyo' => 'cabbage',
        'carrots' => 'carrot',
        'karot' => 'carrot',
        'patatas' => 'potato',
        'potatoes' => 'potato',
        'kamote' => 'sweet-potato',
        'saging' => 'banana',
        'bananas' => 'banana',
        'mangga' => 'mango',
        'pinya' => 'pineapple',
    ];

    public function run(): void
    {
        foreach (self::ALIASES as $alias => $slug) {
            CropPriceAlias::updateOrCreate(
                ['alias_slug' => $alias],
                ['crop_slug' => $slug, 'source' => CropPriceAlias::SOURCE_SEED, 'verified' => true]
            );
        }

        CropPriceAlias::where('source', CropPriceAlias::SOURCE_SEED)
            ->whereNotIn('alias_slug', array_keys(self::ALIASES))
            ->delete();
    }
}
