<?php

namespace Domain\Contact\Actions;

use Domain\Contact\DTOs\CreateContactMessageDTO;
use Domain\Contact\Jobs\SendContactNotificationJob;
use Domain\Contact\Models\ContactMessage;

class CreateContactMessageAction
{
    public function __invoke(CreateContactMessageDTO $dto): ContactMessage
    {
        $message = ContactMessage::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'subject' => $dto->subject,
            'message' => $dto->message,
        ]);

        SendContactNotificationJob::dispatch($message);

        return $message;
    }
}
