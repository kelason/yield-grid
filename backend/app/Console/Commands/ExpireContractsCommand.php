<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Events\ContractExpired;
use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Console\Command;

final class ExpireContractsCommand extends Command
{
    protected $signature = 'marketplace:expire-contracts';
    protected $description = 'Mark available contracts as expired if their expiry date has passed';

    public function handle(): void
    {
        $expiredContracts = ForwardContract::where('status', ContractStatus::AVAILABLE)
            ->where('expiry_date', '<', now()->toDateString())
            ->get();

        $count = $expiredContracts->count();

        if ($count === 0) {
            $this->info('No contracts to expire today.');
            return;
        }

        foreach ($expiredContracts as $contract) {
            $contract->update(['status' => ContractStatus::EXPIRED]);
            event(new ContractExpired($contract));
        }

        $this->info("Successfully expired {$count} contracts.");
    }
}
