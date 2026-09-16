<?php

namespace App\Auth\Controllers;

use App\Http\Controllers\Controller;
use Domain\Users\Actions\VerifyEmailAction;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request, VerifyEmailAction $action): JsonResponse
    {
        $action($request);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent',
        ]);
    }
}
