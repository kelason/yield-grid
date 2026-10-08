<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
final class AdminOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'users' => [
                'members_total' => (int) ($payload['users']['members_total'] ?? 0),
                'members_suspended' => (int) ($payload['users']['members_suspended'] ?? 0),
            ],
            'content' => [
                'thread' => $this->contentBucket($payload, 'thread'),
                'reply' => $this->contentBucket($payload, 'reply'),
                'contract' => $this->contentBucket($payload, 'contract'),
                'listing' => $this->contentBucket($payload, 'listing'),
                'demand' => $this->contentBucket($payload, 'demand'),
            ],
            'inquiries' => [
                'unread' => (int) ($payload['inquiries']['unread'] ?? 0),
                'read' => (int) ($payload['inquiries']['read'] ?? 0),
                'replied' => (int) ($payload['inquiries']['replied'] ?? 0),
                'closed' => (int) ($payload['inquiries']['closed'] ?? 0),
                'failed_replies' => (int) ($payload['inquiries']['failed_replies'] ?? 0),
            ],
            'reports' => [
                'open' => (int) ($payload['reports']['open'] ?? 0),
                'reviewing' => (int) ($payload['reports']['reviewing'] ?? 0),
                'resolved' => (int) ($payload['reports']['resolved'] ?? 0),
                'dismissed' => (int) ($payload['reports']['dismissed'] ?? 0),
            ],
            'issues' => [
                'open' => (int) ($payload['issues']['open'] ?? 0),
                'in_progress' => (int) ($payload['issues']['in_progress'] ?? 0),
                'resolved' => (int) ($payload['issues']['resolved'] ?? 0),
                'closed' => (int) ($payload['issues']['closed'] ?? 0),
            ],
            'generated_at' => (string) ($payload['generated_at'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{total: int, visible: int, hidden: int}
     */
    private function contentBucket(array $payload, string $type): array
    {
        $bucket = $payload['content'][$type] ?? null;

        if (! is_array($bucket)) {
            return ['total' => 0, 'visible' => 0, 'hidden' => 0];
        }

        return [
            'total' => (int) ($bucket['total'] ?? 0),
            'visible' => (int) ($bucket['visible'] ?? 0),
            'hidden' => (int) ($bucket['hidden'] ?? 0),
        ];
    }
}
