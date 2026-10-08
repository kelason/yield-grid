<?php

declare(strict_types=1);

namespace Domain\Users\Actions;

use App\Constants\AdminConstants;
use App\Domain\Users\Exceptions\UserSuspendedException;
use Domain\Users\DTOs\LoginUserDTO;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     * @throws UserSuspendedException
     */
    public function __invoke(LoginUserDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        return DB::transaction(function () use ($user, $dto): array {
            $locked = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($locked->isSuspended()) {
                throw new UserSuspendedException;
            }

            $token = $locked->createToken('auth_token', ['*'], $this->tokenExpiry($locked, $dto->remember))->plainTextToken;

            return [
                'user' => $locked,
                'token' => $token,
            ];
        });
    }

    private function tokenExpiry(User $user, bool $remember): ?Carbon
    {
        if ($user->role === UserRole::ADMIN) {
            return now()->addMinutes(AdminConstants::ADMIN_TOKEN_EXPIRATION_MINUTES);
        }

        return $remember ? null : now()->addHours(2);
    }
}
