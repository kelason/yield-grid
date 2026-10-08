<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Constants\ReportingConstants;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Enums\ReportTargetType;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class ContentTargetResolver
{
    public function find(ReportTargetType $type, string $id): Model
    {
        $key = (int) $id;

        return match ($type) {
            ReportTargetType::THREAD => ForumThread::whereKey($key)->firstOrFail(),
            ReportTargetType::REPLY => ForumReply::whereKey($key)->firstOrFail(),
            ReportTargetType::CONTRACT => ForwardContract::whereKey($key)->firstOrFail(),
            ReportTargetType::LISTING => HarvestListing::whereKey($key)->firstOrFail(),
            ReportTargetType::DEMAND => CropDemand::whereKey($key)->firstOrFail(),
        };
    }

    public function assertReportable(User $reporter, Model $target): void
    {
        $this->assertAccessible($target);
        $this->assertNotOwned($reporter, $target);
    }

    /**
     * @return array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}
     */
    public function snapshot(Model $target): array
    {
        return match (true) {
            $target instanceof ForumThread => $this->forumThreadSnapshot($target),
            $target instanceof ForumReply => $this->forumReplySnapshot($target),
            $target instanceof ForwardContract => $this->marketplaceSnapshot(
                ReportTargetType::CONTRACT, $target->getKey(), $target->farmer_id,
                (string) $target->title, (string) ($target->description ?? ''), $target->status->value
            ),
            $target instanceof HarvestListing => $this->marketplaceSnapshot(
                ReportTargetType::LISTING, $target->getKey(), $target->farmer_id,
                (string) $target->title, (string) ($target->description ?? ''), $target->status->value
            ),
            $target instanceof CropDemand => $this->marketplaceSnapshot(
                ReportTargetType::DEMAND, $target->getKey(), $target->buyer_id,
                (string) $target->title, (string) ($target->description ?? ''), $target->status->value
            ),
            default => throw new InvalidArgumentException('Unsupported report target.'),
        };
    }

    private function assertAccessible(Model $target): void
    {
        if (! $target instanceof ForumReply) {
            return;
        }

        ForumThread::whereKey($target->thread_id)->firstOrFail();
        $this->assertReplyAncestry($target);
    }

    private function assertReplyAncestry(ForumReply $reply): void
    {
        $seen = [$reply->getKey()];
        $parentId = $reply->parent_id;

        while ($parentId !== null) {
            if (in_array($parentId, $seen, true)) {
                throw (new ModelNotFoundException)->setModel(ForumReply::class);
            }

            $seen[] = $parentId;
            $parent = ForumReply::whereKey($parentId)->first();

            if (! $parent instanceof ForumReply) {
                throw (new ModelNotFoundException)->setModel(ForumReply::class);
            }

            $parentId = $parent->parent_id;
        }
    }

    private function assertNotOwned(User $reporter, Model $target): void
    {
        $ownerId = match (true) {
            $target instanceof ForumThread, $target instanceof ForumReply => $target->user_id,
            $target instanceof ForwardContract, $target instanceof HarvestListing => $target->farmer_id,
            $target instanceof CropDemand => $target->buyer_id,
            default => throw new InvalidArgumentException('Unsupported report target.'),
        };

        if ((int) $ownerId === $reporter->id) {
            throw new InvalidArgumentException(ReportingConstants::SELF_REPORT_MESSAGE);
        }
    }

    /**
     * @return array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}
     */
    private function forumThreadSnapshot(ForumThread $thread): array
    {
        return $this->baseSnapshot(
            ReportTargetType::THREAD, $thread->getKey(), $thread->user_id,
            (string) $thread->title, (string) $thread->body, null
        );
    }

    /**
     * @return array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}
     */
    private function forumReplySnapshot(ForumReply $reply): array
    {
        $thread = ForumThread::whereKey($reply->thread_id)->first();

        return $this->baseSnapshot(
            ReportTargetType::REPLY, $reply->getKey(), $reply->user_id,
            (string) ($thread->title ?? ''), (string) $reply->body, null
        );
    }

    /**
     * @return array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}
     */
    private function marketplaceSnapshot(
        ReportTargetType $type,
        int $id,
        int $ownerId,
        string $title,
        string $body,
        string $status,
    ): array {
        return $this->baseSnapshot($type, $id, $ownerId, $title, $body, $status);
    }

    /**
     * @return array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}
     */
    private function baseSnapshot(
        ReportTargetType $type,
        int $id,
        int $ownerId,
        string $title,
        string $body,
        ?string $status,
    ): array {
        return [
            'type' => $type->value,
            'id' => (string) $id,
            'owner_id' => (string) $ownerId,
            'title' => mb_substr($title, 0, ReportingConstants::SNAPSHOT_FIELD_MAX_LENGTH),
            'excerpt' => mb_substr($body, 0, ReportingConstants::SNAPSHOT_FIELD_MAX_LENGTH),
            'status' => $status,
            'captured_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
