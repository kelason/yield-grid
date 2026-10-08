<?php

declare(strict_types=1);

namespace App\Shared\Resources;

use App\Domain\Shared\Models\ContentReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentReport
 */
final class ContentReportReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status->value,
        ];
    }
}
