<?php

namespace Domain\Users\Actions;

use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailAction
{
    public function __invoke(EmailVerificationRequest $request): void
    {
        $request->fulfill();
    }
}
