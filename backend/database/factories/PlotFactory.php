<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plot>
 */
class PlotFactory extends Factory
{
    protected $model = Plot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'name' => fake()->words(2, true),
        ];
    }
}
