<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Insurance\Actions\SendInsuranceRemindersAction;
use Illuminate\Console\Command;

final class SendInsuranceRemindersCommand extends Command
{
    protected $signature = 'insurance:send-reminders';

    protected $description = 'Send due PCIC insurance reminders to farmers (daily cron)';

    public function handle(SendInsuranceRemindersAction $action): void
    {
        $count = $action->execute();

        $this->info("Successfully sent {$count} insurance reminders.");
    }
}
