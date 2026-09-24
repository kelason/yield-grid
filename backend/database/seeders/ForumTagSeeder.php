<?php

namespace Database\Seeders;

use App\Domain\Community\Models\ForumTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ForumTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Rice', 'Corn', 'Vegetables', 'Fruits', 'Organic',
            'Pest Control', 'Fertilizer', 'Irrigation', 'Harvest',
            'Market Price', 'Contract', 'Logistics', 'Weather Alert',
            'Pesticides', 'Insects', 'Weeds', 'Disease',
            'Pumps', 'Drip Irrigation', 'Water Supply',
            'Compost', 'Sustainable', 'Permaculture',
            'Cattle', 'Poultry', 'Swine', 'Animal Feed',
            'Loans', 'Grants', 'Subsidies', 'Insurance',
        ];

        foreach ($tags as $tagName) {
            ForumTag::updateOrCreate([
                'slug' => Str::slug($tagName),
            ], [
                'name' => $tagName,
            ]);
        }
    }
}
