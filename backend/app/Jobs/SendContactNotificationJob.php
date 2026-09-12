<?php

namespace App\Jobs;

use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendContactNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactMessage $contactMessage
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("New contact message from {$this->contactMessage->email}");
        // In a real app, send email: Mail::to(config('mail.from.address'))->send(...)
    }
}
