<?php

namespace Database\Factories;

use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Marketplace\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->buyer(),
            'forward_contract_id' => ForwardContract::factory(),
            'paymongo_payment_id' => 'pi_' . $this->faker->uuid(),
            'paymongo_checkout_id' => 'cs_' . $this->faker->uuid(),
            'payment_method' => $this->faker->randomElement([PaymentMethod::GCASH, PaymentMethod::MAYA, PaymentMethod::CARD]),
            'amount_paid' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'PHP',
            'payment_status' => PaymentStatus::PENDING,
            'purchased_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::COMPLETED,
            'purchased_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }
}
