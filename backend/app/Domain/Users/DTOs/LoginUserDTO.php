<?php

namespace Domain\Users\DTOs;

readonly class LoginUserDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'],
            password: $validated['password'],
        );
    }
}
