<?php

namespace App\Farming\Controllers;

use App\Farming\Requests\StoreFarmRequest;
use App\Farming\Resources\FarmResource;
use App\Http\Controllers\Controller;
use Domain\Farming\Actions\CreateFarmAction;
use Domain\Farming\Actions\GetFarmsAction;
use Domain\Farming\DTOs\CreateFarmDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FarmController extends Controller
{
    public function index(Request $request, GetFarmsAction $action): AnonymousResourceCollection
    {
        $farms = $action($request->user()->id);

        return FarmResource::collection($farms);
    }

    public function store(StoreFarmRequest $request, CreateFarmAction $action): JsonResponse
    {
        $dto = CreateFarmDTO::fromRequest($request->validated(), $request->user()->id);
        $farm = $action($dto);

        return response()->json([
            'message' => 'Farm created successfully',
            'data' => new FarmResource($farm),
        ], 201);
    }
}
