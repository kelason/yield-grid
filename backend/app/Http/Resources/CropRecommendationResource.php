<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CropRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plot_id' => $this->plot_id,
            'crop_name' => $this->crop_name,
            'confidence_score' => $this->confidence_score,
            'reasoning' => $this->reasoning,
            'projected_yield' => $this->projected_yield,
            'status' => $this->status->value ?? $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
