<?php

namespace Domain\Users\Actions;

use Domain\Users\DTOs\RegisterUserDTO;
use Domain\Users\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function __invoke(RegisterUserDTO $dto): User
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'role' => $dto->role,
        ]);

        event(new Registered($user));

        return $user;
    }
}
