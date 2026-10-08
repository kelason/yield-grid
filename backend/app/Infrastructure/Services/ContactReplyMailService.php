<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\ContactConstants;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

final class ContactReplyMailService
{
    public function send(string $recipient, string $body): void
    {
        Mail::raw($body, function (Message $message) use ($recipient): void {
            $message->to($recipient)->subject(ContactConstants::REPLY_EMAIL_SUBJECT);
        });
    }
}
