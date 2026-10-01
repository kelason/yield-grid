<?php

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Models\ForwardContract;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->buyer = User::factory()->buyer()->create();
    $this->farmer = User::factory()->farmer()->create();
    $this->makeContract = function (float $quantityKg): ForwardContract {
        return ForwardContract::factory()->available()->create([
            'farmer_id' => $this->farmer->id,
            'quantity_kg' => $quantityKg,
            'price_per_kg' => 10,
            'total_price' => $quantityKg * 10,
        ]);
    };
});

it('accepts checkout quantity at the min', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MIN_KG,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects checkout quantity below the min', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => 0,
        'payment_option' => 'cash',
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts checkout quantity at the max', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects checkout quantity above the max', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG + 1,
        'payment_option' => 'cash',
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('rejects checkout quantity above availability with conflict', function () {
    $contract = ($this->makeContract)(100);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => 500,
        'payment_option' => 'cash',
    ])->assertStatus(409);
});
