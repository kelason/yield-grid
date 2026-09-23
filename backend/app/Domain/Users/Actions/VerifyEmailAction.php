<?php

namespace Domain\Users\Actions;

use App\Constants\HttpCode;
use Domain\Users\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailAction
{
    public function __invoke(Request $request): void
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            abort(HttpCode::FORBIDDEN, 'Invalid signature.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
    }
}
