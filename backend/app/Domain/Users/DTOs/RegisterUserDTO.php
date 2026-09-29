<?php

namespace Domain\Users\DTOs;

readonly class RegisterUserDTO
{
    /**
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role,
        public ?array $address = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            role: $validated['role'],
            address: $validated['address'] ?? null,
        );
    }
}
