<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\ContactConstants;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Infrastructure\Services\ContactReplyMailService;
use Domain\Contact\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendContactReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = ContactConstants::REPLY_MAX_ATTEMPTS;

    public int $timeout = ContactConstants::REPLY_TIMEOUT_SECONDS;

    public ?int $claimedGeneration = null;

    public function __construct(public readonly int $replyId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return ContactConstants::REPLY_BACKOFF_SECONDS;
    }

    public function handle(
        ContactReplyMailService $mail,
        ContactMessageReplyRepositoryInterface $replies,
    ): void {
        $claim = $this->claim($replies);

        if ($claim === null) {
            return;
        }

        try {
            $mail->send($claim['recipient'], $claim['body']);
        } catch (Throwable $exception) {
            $this->releaseClaim($replies, $claim['generation']);

            throw $exception;
        }

        $this->complete($replies, $claim['generation']);
    }

    public function failed(Throwable $exception): void
    {
        $replies = app(ContactMessageReplyRepositoryInterface::class);

        DB::transaction(function () use ($replies): void {
            try {
                $probe = $replies->findById($this->replyId);
            } catch (ModelNotFoundException) {
                return;
            }

            ContactMessage::where('id', $probe->message_id)->lockForUpdate()->first();
            $locked = $replies->findLockedById($this->replyId);

            if ($locked->delivery_status === ReplyDeliveryStatus::SENT) {
                return;
            }

            if ($this->claimedGeneration !== null && $locked->delivery_generation !== $this->claimedGeneration) {
                return;
            }

            $locked->forceFill([
                'delivery_status' => ReplyDeliveryStatus::FAILED,
                'sending_started_at' => null,
                'error_code' => ContactConstants::REPLY_ERROR_TRANSPORT_FAILED,
            ]);
            $replies->save($locked);
        });
    }

    /**
     * @return array{recipient: string, body: string, generation: int}|null
     */
    private function claim(ContactMessageReplyRepositoryInterface $replies): ?array
    {
        return DB::transaction(function () use ($replies): ?array {
            try {
                $probe = $replies->findById($this->replyId);
            } catch (ModelNotFoundException) {
                return null;
            }

            ContactMessage::where('id', $probe->message_id)->lockForUpdate()->first();
            $locked = $replies->findLockedById($this->replyId);

            if ($locked->delivery_status !== ReplyDeliveryStatus::QUEUED) {
                return null;
            }

            return $this->writeClaim($replies, $locked);
        });
    }

    /**
     * @return array{recipient: string, body: string, generation: int}
     */
    private function writeClaim(
        ContactMessageReplyRepositoryInterface $replies,
        ContactMessageReply $locked,
    ): array {
        $locked->forceFill([
            'delivery_status' => ReplyDeliveryStatus::SENDING,
            'delivery_generation' => $locked->delivery_generation + 1,
            'sending_started_at' => now(),
            'attempts' => $locked->attempts + 1,
        ]);
        $replies->save($locked);

        $this->claimedGeneration = $locked->delivery_generation;

        return [
            'recipient' => $locked->recipient,
            'body' => $locked->body,
            'generation' => $locked->delivery_generation,
        ];
    }

    private function releaseClaim(ContactMessageReplyRepositoryInterface $replies, int $generation): void
    {
        DB::transaction(function () use ($replies, $generation): void {
            $this->lockedForCompletion($replies, $generation, function (ContactMessageReply $locked) use ($replies): void {
                $locked->forceFill([
                    'delivery_status' => ReplyDeliveryStatus::QUEUED,
                    'sending_started_at' => null,
                    'error_code' => ContactConstants::REPLY_ERROR_TRANSPORT_FAILED,
                ]);
                $replies->save($locked);
            });
        });
    }

    private function complete(ContactMessageReplyRepositoryInterface $replies, int $generation): void
    {
        DB::transaction(function () use ($replies, $generation): void {
            $this->lockedForCompletion($replies, $generation, function (ContactMessageReply $locked) use ($replies): void {
                $locked->forceFill([
                    'delivery_status' => ReplyDeliveryStatus::SENT,
                    'sending_started_at' => null,
                    'sent_at' => now(),
                    'error_code' => null,
                ]);
                $replies->save($locked);

                $parent = ContactMessage::where('id', $locked->message_id)->lockForUpdate()->first();

                if ($parent instanceof ContactMessage && $parent->status !== ContactStatus::CLOSED) {
                    $parent->forceFill(['status' => ContactStatus::REPLIED, 'replied_at' => now()])->save();
                }
            });
        });
    }

    /**
     * @param  callable(ContactMessageReply): void  $apply
     */
    private function lockedForCompletion(
        ContactMessageReplyRepositoryInterface $replies,
        int $generation,
        callable $apply,
    ): void {
        try {
            $probe = $replies->findById($this->replyId);
        } catch (ModelNotFoundException) {
            return;
        }

        ContactMessage::where('id', $probe->message_id)->lockForUpdate()->first();
        $locked = $replies->findLockedById($this->replyId);

        if ($locked->delivery_status !== ReplyDeliveryStatus::SENDING) {
            return;
        }

        if ($locked->delivery_generation !== $generation) {
            return;
        }

        $apply($locked);
    }
}
