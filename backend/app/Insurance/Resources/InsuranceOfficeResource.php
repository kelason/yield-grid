<?php

declare(strict_types=1);

namespace App\Insurance\Resources;

use App\Domain\Insurance\Models\InsuranceOffice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InsuranceOffice
 */
final class InsuranceOfficeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'region_code' => $this->region_code,
            'city' => $this->city,
            'address' => $this->address,
            'phone' => $this->phone,
            'source_note' => $this->source_note,
            'is_head_office' => $this->region_code === null,
        ];
    }
}
