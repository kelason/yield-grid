<?php

namespace Domain\Users\Actions;

use Domain\Users\DTOs\ForgotPasswordDTO;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class SendPasswordResetLinkAction
{
    /**
     * @throws ValidationException
     */
    public function __invoke(ForgotPasswordDTO $dto): string
    {
        $status = Password::sendResetLink(
            ['email' => $dto->email]
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
