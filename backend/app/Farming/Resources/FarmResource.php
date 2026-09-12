<?php

namespace App\Farming\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'total_area' => $this->total_area,
            'plots_count' => $this->whenCounted('plots'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
