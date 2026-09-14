<?php

namespace Domain\Users\Actions;

use Domain\Users\DTOs\LoginUserDTO;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * @return array{user: User, token: string}
     * @throws ValidationException
     */
    public function __invoke(LoginUserDTO $dto): array
    {
        if (!Auth::attempt(['email' => $dto->email, 'password' => $dto->password])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = User::where('email', $dto->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
