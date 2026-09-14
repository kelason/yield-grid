<?php

namespace Domain\Contact\DTOs;

readonly class CreateContactMessageDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $subject,
        public string $message,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            subject: $validated['subject'] ?? null,
            message: $validated['message'],
        );
    }
}
