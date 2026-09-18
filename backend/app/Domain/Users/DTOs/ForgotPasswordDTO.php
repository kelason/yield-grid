<?php

namespace Domain\Users\DTOs;

readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
    ) {}

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'],
        );
    }
}
