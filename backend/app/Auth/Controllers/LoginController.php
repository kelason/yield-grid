<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Requests\LoginRequest;
use App\Auth\Resources\UserResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Domain\Users\Exceptions\UserSuspendedException;
use App\Shared\Controllers\Controller;
use Domain\Users\Actions\LoginUserAction;
use Domain\Users\DTOs\LoginUserDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $dto = LoginUserDTO::fromRequest($request->validated());

        try {
            $result = $action($dto);
        } catch (UserSuspendedException) {
            return response()->json([
                'message' => AdminConstants::SUSPENDED_MESSAGE,
                'code' => AdminConstants::SUSPENDED_CODE,
            ], HttpCode::FORBIDDEN);
        }

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
