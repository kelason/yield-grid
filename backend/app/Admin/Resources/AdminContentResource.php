<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Enums\ReportTargetType;
use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

/**
 * Admin-only content rendering across all five reportable types. Internal
 * moderation notes and author identity are exposed here and must never leak
 * into member resources.
 */
final class AdminContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        return match (true) {
            $model instanceof ForumThread => $this->threadArray($model),
            $model instanceof ForumReply => $this->replyArray($model),
            $model instanceof ForwardContract => $this->contractArray($model),
            $model instanceof HarvestListing => $this->listingArray($model),
            $model instanceof CropDemand => $this->demandArray($model),
            default => throw new InvalidArgumentException('Unsupported content target.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function threadArray(ForumThread $thread): array
    {
        return [
            'type' => ReportTargetType::THREAD->value,
            'id' => (string) $thread->id,
            'title' => $thread->title,
            'body' => $thread->body,
            'category_id' => (string) $thread->category_id,
            'reply_count' => (int) $thread->reply_count,
            'author' => $this->authorArray($thread->getRelationValue('author')),
            ...$this->moderationArray($thread->hidden_at, $thread->hidden_by, $thread->hidden_reason),
            'created_at' => $thread->created_at,
            'updated_at' => $thread->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function replyArray(ForumReply $reply): array
    {
        $thread = $reply->getRelationValue('thread');

        return [
            'type' => ReportTargetType::REPLY->value,
            'id' => (string) $reply->id,
            'thread_id' => (string) $reply->thread_id,
            'parent_id' => $reply->parent_id === null ? null : (string) $reply->parent_id,
            'title' => $thread instanceof ForumThread ? (string) $thread->title : '',
            'body' => $reply->body,
            'author' => $this->authorArray($reply->getRelationValue('author')),
            ...$this->moderationArray($reply->hidden_at, $reply->hidden_by, $reply->hidden_reason),
            'created_at' => $reply->created_at,
            'updated_at' => $reply->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contractArray(ForwardContract $contract): array
    {
        return [
            'type' => ReportTargetType::CONTRACT->value,
            'id' => (string) $contract->id,
            'title' => $contract->title,
            'description' => $contract->description,
            'crop_name' => $contract->crop_name,
            'status' => $contract->status->value,
            'author' => $this->authorArray($contract->getRelationValue('farmer')),
            ...$this->moderationArray($contract->hidden_at, $contract->hidden_by, $contract->hidden_reason),
            'moderation_root_id' => $contract->moderation_root_id === null ? null : (string) $contract->moderation_root_id,
            'affected_root_id' => (string) ($contract->moderation_root_id ?? $contract->id),
            'is_effectively_hidden' => $contract->isEffectivelyHidden(),
            'created_at' => $contract->created_at,
            'updated_at' => $contract->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listingArray(HarvestListing $listing): array
    {
        return [
            'type' => ReportTargetType::LISTING->value,
            'id' => (string) $listing->id,
            'title' => $listing->title,
            'description' => $listing->description,
            'crop_name' => $listing->crop_name,
            'status' => $listing->status->value,
            'author' => $this->authorArray($listing->getRelationValue('farmer')),
            ...$this->moderationArray($listing->hidden_at, $listing->hidden_by, $listing->hidden_reason),
            'moderation_root_id' => $listing->moderation_root_id === null ? null : (string) $listing->moderation_root_id,
            'affected_root_id' => (string) ($listing->moderation_root_id ?? $listing->id),
            'is_effectively_hidden' => $listing->isEffectivelyHidden(),
            'created_at' => $listing->created_at,
            'updated_at' => $listing->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demandArray(CropDemand $demand): array
    {
        return [
            'type' => ReportTargetType::DEMAND->value,
            'id' => (string) $demand->id,
            'title' => $demand->title,
            'description' => $demand->description,
            'crop_name' => $demand->crop_name,
            'status' => $demand->status->value,
            'author' => $this->authorArray($demand->getRelationValue('buyer')),
            ...$this->moderationArray($demand->hidden_at, $demand->hidden_by, $demand->hidden_reason),
            'created_at' => $demand->created_at,
            'updated_at' => $demand->updated_at,
        ];
    }

    /**
     * @return array{id: string, name: string, email: string}|null
     */
    private function authorArray(mixed $author): ?array
    {
        if (! $author instanceof User) {
            return null;
        }

        return [
            'id' => (string) $author->id,
            'name' => (string) $author->name,
            'email' => (string) $author->email,
        ];
    }

    /**
     * @return array{is_hidden: bool, hidden_at: mixed, hidden_by: ?string, hidden_reason: ?string}
     */
    private function moderationArray(mixed $hiddenAt, mixed $hiddenBy, mixed $hiddenReason): array
    {
        return [
            'is_hidden' => $hiddenAt !== null,
            'hidden_at' => $hiddenAt,
            'hidden_by' => $hiddenBy === null ? null : (string) $hiddenBy,
            'hidden_reason' => $hiddenReason,
        ];
    }
}
