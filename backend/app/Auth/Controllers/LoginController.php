<?php

namespace App\Auth\Controllers;

use App\Auth\Requests\LoginRequest;
use App\Auth\Resources\UserResource;
use App\Http\Controllers\Controller;
use Domain\Users\Actions\LoginUserAction;
use Domain\Users\DTOs\LoginUserDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $dto = LoginUserDTO::fromRequest($request->validated());
        $result = $action($dto);

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
