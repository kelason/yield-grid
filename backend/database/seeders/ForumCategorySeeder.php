<?php

namespace Database\Seeders;

use App\Domain\Community\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'General Discussion',
                'slug' => 'general',
                'description' => 'Open conversation about farming and agriculture',
                'icon_emoji' => '💬',
                'sort_order' => 10,
            ],
            [
                'name' => 'Crop Help & Advice',
                'slug' => 'crop-help',
                'description' => 'Ask questions about crop health, planting, and harvesting',
                'icon_emoji' => '🌱',
                'sort_order' => 20,
            ],
            [
                'name' => 'Soil & Land Management',
                'slug' => 'soil-management',
                'description' => 'Soil types, fertilization, land preparation topics',
                'icon_emoji' => '🏔️',
                'sort_order' => 30,
            ],
            [
                'name' => 'Weather & Climate',
                'slug' => 'weather',
                'description' => 'Weather patterns, climate adaptation, seasonal planning',
                'icon_emoji' => '🌦️',
                'sort_order' => 40,
            ],
            [
                'name' => 'Marketplace Talk',
                'slug' => 'marketplace',
                'description' => 'Buying, selling, pricing, and contract discussions',
                'icon_emoji' => '🛒',
                'sort_order' => 50,
            ],
            [
                'name' => 'Tools & Equipment',
                'slug' => 'tools-equipment',
                'description' => 'Farm equipment, tools, and technology recommendations',
                'icon_emoji' => '🔧',
                'sort_order' => 60,
            ],
            [
                'name' => 'Pest & Disease Control',
                'slug' => 'pest-control',
                'description' => 'Managing insects, weeds, and plant diseases',
                'icon_emoji' => '🐛',
                'sort_order' => 45,
            ],
            [
                'name' => 'Irrigation & Water',
                'slug' => 'irrigation',
                'description' => 'Water management, pumps, and irrigation systems',
                'icon_emoji' => '💧',
                'sort_order' => 47,
            ],
            [
                'name' => 'Organic & Sustainable',
                'slug' => 'organic',
                'description' => 'Organic farming practices and sustainability',
                'icon_emoji' => '🌿',
                'sort_order' => 55,
            ],
            [
                'name' => 'Livestock & Poultry',
                'slug' => 'livestock',
                'description' => 'Animal husbandry and care',
                'icon_emoji' => '🐄',
                'sort_order' => 65,
            ],
            [
                'name' => 'Success Stories',
                'slug' => 'success-stories',
                'description' => 'Share your wins — great harvests, solved problems',
                'icon_emoji' => '🏆',
                'sort_order' => 70,
            ],
            [
                'name' => 'Farm Finance & Grants',
                'slug' => 'finance',
                'description' => 'Discuss loans, subsidies, and farm accounting',
                'icon_emoji' => '💵',
                'sort_order' => 80,
            ],
        ];

        foreach ($categories as $category) {
            ForumCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
