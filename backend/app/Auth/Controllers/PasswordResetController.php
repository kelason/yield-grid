<?php

namespace App\Auth\Controllers;

use App\Auth\Actions\ResetPasswordAction;
use App\Auth\Actions\SendPasswordResetLinkAction;
use App\Auth\Requests\ForgotPasswordRequest;
use App\Auth\Requests\ResetPasswordRequest;
use App\Http\Controllers\Controller;
use Domain\Users\DTOs\ForgotPasswordDTO;
use Domain\Users\DTOs\ResetPasswordDTO;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $dto = ForgotPasswordDTO::fromRequest($request->validated());
        $message = $action($dto);

        return response()->json(['message' => $message]);
    }

    public function reset(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $dto = ResetPasswordDTO::fromRequest($request->validated());
        $message = $action($dto);

        return response()->json(['message' => $message]);
    }
}
