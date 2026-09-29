<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CancelDemandAction
{
    public function execute(CropDemand $demand): CropDemand
    {
        return DB::transaction(function () use ($demand): CropDemand {
            $locked = CropDemand::where('id', $demand->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [DemandStatus::OPEN, DemandStatus::FULLY_ALLOCATED], true)) {
                throw new LogicException('Only open or allocated demands can be cancelled.');
            }

            $locked->offers()
                ->where('status', DemandOfferStatus::PENDING)
                ->update(['status' => DemandOfferStatus::CANCELLED]);

            $locked->update(['status' => DemandStatus::CANCELLED]);

            return $locked->fresh() ?? $locked;
        });
    }
}
