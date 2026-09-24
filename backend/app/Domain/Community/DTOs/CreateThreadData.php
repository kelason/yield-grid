<?php

declare(strict_types=1);

namespace App\Domain\Community\DTOs;

final readonly class CreateThreadData
{
    /**
     * @param  array<int>  $tagIds
     */
    public function __construct(
        public int $userId,
        public int $categoryId,
        public string $title,
        public string $body,
        public bool $isAnonymous,
        public array $tagIds = [],
    ) {}
}
