<?php

declare(strict_types=1);

namespace Domain\Users\Actions;

use Domain\Users\DTOs\RegisterUserDTO;
use Domain\Users\DTOs\UpsertUserAddressDTO;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class RegisterUserAction
{
    public function __construct(
        private readonly CreateUserAddressAction $createAddressAction,
    ) {}

    public function __invoke(RegisterUserDTO $dto): User
    {
        if (! in_array($dto->role, [UserRole::FARMER->value, UserRole::BUYER->value], true)) {
            throw new InvalidArgumentException('Only farmer or buyer roles can register.');
        }

        $user = DB::transaction(function () use ($dto): User {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'role' => $dto->role,
                'locale' => $dto->locale,
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
