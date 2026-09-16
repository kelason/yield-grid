<?php

namespace Database\Factories;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForwardContract>
 */
class ForwardContractFactory extends Factory
{
    protected $model = ForwardContract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 50, 1000);
        $price = $this->faker->randomFloat(2, 20, 200);

        return [
            'farmer_id' => User::factory()->farmer(),
            'crop_recommendation_id' => 1,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'crop_name' => 'Rice (Jasmine)',
            'quantity_kg' => $quantity,
            'price_per_kg' => $price,
            'total_price' => $quantity * $price,
            'currency' => 'PHP',
            'estimated_harvest_date' => now()->addDays(30),
            'expiry_date' => now()->addDays(15),
            'status' => ContractStatus::AVAILABLE,
        ];
    }

    public function available(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::AVAILABLE,
        ]);
    }

    public function sold(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::SOLD,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::EXPIRED,
            'expiry_date' => now()->subDay(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::CANCELLED,
        ]);
    }
}
