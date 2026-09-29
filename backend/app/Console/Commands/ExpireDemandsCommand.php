<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Events\DemandExpired;
use App\Domain\Marketplace\Models\CropDemand;
use Illuminate\Console\Command;

final class ExpireDemandsCommand extends Command
{
    protected $signature = 'marketplace:expire-demands';

    protected $description = 'Mark open demands as expired if their expiry date has passed, and expire their pending offers';

    public function handle(): void
    {
        $expiredDemands = CropDemand::where('status', DemandStatus::OPEN)
            ->where('expiry_date', '<', now()->toDateString())
            ->get();

        $count = $expiredDemands->count();

        if ($count === 0) {
            $this->info('No demands to expire today.');

            return;
        }

        foreach ($expiredDemands as $demand) {
            $demand->offers()->where('status', DemandOfferStatus::PENDING)->update(['status' => DemandOfferStatus::EXPIRED]);
            $demand->update(['status' => DemandStatus::EXPIRED]);
            event(new DemandExpired($demand));
        }

        $this->info("Successfully expired {$count} demands.");
    }
}
