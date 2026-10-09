<?php

declare(strict_types=1);

namespace App\Farming\Resources;

use Domain\Farming\Enums\VerificationStatus;
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
            'country' => $this->country,
            'zip' => $this->zip,
            'total_area' => $this->total_area,
            'plots_count' => $this->whenCounted('plots'),
            'verification_status' => $this->verification_status?->value,
            'verification_note' => $this->when($this->verification_status === VerificationStatus::REJECTED, $this->verification_note),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
