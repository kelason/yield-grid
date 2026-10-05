<?php

declare(strict_types=1);

namespace App\Insurance\Resources;

use App\Domain\Insurance\Models\InsuranceProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InsuranceProfile
 */
final class InsuranceProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rsbsa_number' => $this->rsbsa_number,
            'rsbsa_status' => $this->rsbsa_status->value,
        ];
    }
}
