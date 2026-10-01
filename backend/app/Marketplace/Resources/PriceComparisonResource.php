<?php

declare(strict_types=1);

namespace App\Marketplace\Resources;

use App\Domain\Marketplace\DTOs\PriceComparisonData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PriceComparisonData
 */
class PriceComparisonResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PriceComparisonData $data */
        $data = $this->resource;

        return $data->toArray();
    }
}
