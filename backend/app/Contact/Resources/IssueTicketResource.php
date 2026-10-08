<?php

declare(strict_types=1);

namespace App\Contact\Resources;

use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueTicket
 */
final class IssueTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'category' => $this->enumValue(IssueCategory::class, 'category'),
            'subject' => $this->subject,
            'description' => $this->description,
            'page_path' => $this->page_path,
            'status' => $this->enumValue(IssueStatus::class, 'status'),
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @param  class-string  $enum
     */
    private function enumValue(string $enum, string $column): string
    {
        $raw = (string) $this->getRawOriginal($column);

        return $enum::tryFrom($raw)->value ?? $raw;
    }
}
