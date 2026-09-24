<?php

declare(strict_types=1);

namespace App\Domain\Community\DTOs;

final readonly class CreateReplyData
{
    public function __construct(
        public int $threadId,
        public int $userId,
        public string $body,
        public bool $isAnonymous,
        public ?int $parentId = null,
    ) {}
}
