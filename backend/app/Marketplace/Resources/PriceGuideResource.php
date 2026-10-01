<?php

declare(strict_types=1);

namespace App\Marketplace\Resources;

use App\Domain\Marketplace\DTOs\PriceGuideData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PriceGuideData
 */
class PriceGuideResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PriceGuideData $data */
        $data = $this->resource;

        return $data->toArray();
    }
}
