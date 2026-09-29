<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\CropDemand;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DemandExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CropDemand $demand,
    ) {}
}
