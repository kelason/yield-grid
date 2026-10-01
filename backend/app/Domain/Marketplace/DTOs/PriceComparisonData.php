<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

use App\Domain\Marketplace\Enums\PriceCheckSource;
use App\Domain\Marketplace\Enums\PriceMatchType;

final readonly class PriceComparisonData
{
    /**
     * @param  array<string, array<string, mixed>>  $sections  Keyed by PriceCheckSource value, precedence-ordered.
     */
    public function __construct(
        public bool $available,
        public ?string $reason = null,
        public ?string $message = null,
        public ?string $input = null,
        public bool $pending = false,
        public ?PriceCheckSource $resolvedFrom = null,
        public ?string $cropSlug = null,
        public ?string $cropDisplayName = null,
        public ?PriceMatchType $matchType = null,
        public ?string $correctedFrom = null,
        public array $sections = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'reason' => $this->reason,
            'message' => $this->message,
            'input' => $this->input,
            'pending' => $this->pending,
            'resolved_from' => $this->resolvedFrom?->value,
            'crop_slug' => $this->cropSlug,
            'crop_display_name' => $this->cropDisplayName,
            'match_type' => $this->matchType?->value,
            'corrected_from' => $this->correctedFrom,
            'sections' => $this->sections,
        ];
    }
}
