<?php

namespace App\Auth\Controllers;

use App\Auth\Requests\RegisterRequest;
use App\Auth\Resources\UserResource;
use App\Http\Controllers\Controller;
use Domain\Users\Actions\RegisterUserAction;
use Domain\Users\DTOs\RegisterUserDTO;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $dto = RegisterUserDTO::fromRequest($request->validated());
        $user = $action($dto);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
