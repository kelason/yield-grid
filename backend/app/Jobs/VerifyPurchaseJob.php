<?php

namespace App\Jobs;

use App\Domain\Marketplace\Actions\VerifyPurchaseAction;
use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPurchaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Purchase $purchase) {}

    /**
     * Execute the job.
     */
    public function handle(VerifyPurchaseAction $verifyPurchaseAction): void
    {
        $verifyPurchaseAction->execute($this->purchase);
    }
}
