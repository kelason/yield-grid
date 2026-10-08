<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueTicket
 */
final class AdminIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'user_id' => $this->user_id === null ? null : (string) $this->user_id,
            'category' => $this->enumValue(IssueCategory::class, 'category'),
            'subject' => $this->subject,
            'description' => $this->description,
            'page_path' => $this->page_path,
            'status' => $this->enumValue(IssueStatus::class, 'status'),
            'version' => (int) $this->version,
            'resolution' => $this->resolution,
            'resolved_by' => $this->resolved_by === null ? null : (string) $this->resolved_by,
            'resolved_at' => $this->resolved_at,
            'reporter' => $this->reporterArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array{id: string, name: string, email: string}|null
     */
    private function reporterArray(): ?array
    {
        $reporter = $this->getRelationValue('reporter');

        if (! $reporter instanceof User) {
            return null;
        }

        return [
            'id' => (string) $reporter->id,
            'name' => (string) $reporter->name,
            'email' => (string) $reporter->email,
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
