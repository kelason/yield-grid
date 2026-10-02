<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CreditScoring\Actions\CalculateCreditScoreAction;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Console\Command;

final class RecalculateAllCreditScoresCommand extends Command
{
    protected $signature = 'credit-scoring:recalculate-all';

    protected $description = 'Recalculate credit scores for all farmers (daily cron)';

    public function handle(CalculateCreditScoreAction $action): void
    {
        $farmers = User::where('role', UserRole::FARMER)->cursor();
        $count = 0;

        foreach ($farmers as $farmer) {
            try {
                $action->execute($farmer);
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to calculate score for farmer #{$farmer->id}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully recalculated credit scores for {$count} farmers.");
    }
}
