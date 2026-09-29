<?php

declare(strict_types=1);

namespace App\Users\Controllers;

use App\Constants\HttpCode;
use App\Http\Controllers\Controller;
use App\Users\Requests\StoreUserAddressRequest;
use App\Users\Requests\UpdateUserAddressRequest;
use App\Users\Resources\UserAddressResource;
use Domain\Users\Actions\CreateUserAddressAction;
use Domain\Users\Actions\DeleteUserAddressAction;
use Domain\Users\Actions\UpdateUserAddressAction;
use Domain\Users\DTOs\UpsertUserAddressDTO;
use Domain\Users\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

final class UserAddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->oldest()
            ->get();

        return UserAddressResource::collection($addresses);
    }

    public function store(StoreUserAddressRequest $request, CreateUserAddressAction $action): JsonResponse
    {
        $address = $action(UpsertUserAddressDTO::fromRequest($request->user()->id, $request->validated()));

        return (new UserAddressResource($address))->response()->setStatusCode(HttpCode::CREATED);
    }

    public function update(UpdateUserAddressRequest $request, UserAddress $address, UpdateUserAddressAction $action): UserAddressResource
    {
        $this->authorize('update', $address);

        $address = $action->execute($address, UpsertUserAddressDTO::fromRequest($address->user_id, $request->validated()));

        return new UserAddressResource($address);
    }

    public function destroy(Request $request, UserAddress $address, DeleteUserAddressAction $action): JsonResponse
    {
        $this->authorize('delete', $address);

        try {
            $action->execute($address);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return response()->json(['message' => 'Address deleted.']);
    }
}
