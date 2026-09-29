<?php

namespace Domain\Users\Actions;

use Domain\Users\DTOs\RegisterUserDTO;
use Domain\Users\DTOs\UpsertUserAddressDTO;
use Domain\Users\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function __construct(
        private readonly CreateUserAddressAction $createAddressAction,
    ) {}

    public function __invoke(RegisterUserDTO $dto): User
    {
        $user = DB::transaction(function () use ($dto): User {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'role' => $dto->role,
            ]);

            if ($dto->address !== null) {
                $this->createAddressAction->__invoke(UpsertUserAddressDTO::fromRequest($user->id, array_merge(
                    $dto->address,
                    ['is_default' => true],
                )));
            }

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
