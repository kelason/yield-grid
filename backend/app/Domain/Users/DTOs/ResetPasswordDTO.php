<?php

namespace Domain\Users\DTOs;

readonly class ResetPasswordDTO
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'],
            token: $validated['token'],
            password: $validated['password'],
        );
    }
}
