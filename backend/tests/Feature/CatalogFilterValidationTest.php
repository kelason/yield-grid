<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Constants\HttpCode;
use Tests\TestCase;

final class CatalogFilterValidationTest extends TestCase
{
    public function test_invalid_catalog_filters_return_validation_errors_before_queries(): void
    {
        $this->getJson('/api/v1/market/contracts?min_price=-0.01')
            ->assertStatus(HttpCode::UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('min_price');
        $this->getJson('/api/v1/market/demands?max_budget=10000000000')
            ->assertStatus(HttpCode::UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('max_budget');
    }
}
