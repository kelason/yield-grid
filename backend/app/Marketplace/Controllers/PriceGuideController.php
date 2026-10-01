<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Domain\Marketplace\Actions\GetPriceGuideAction;
use App\Marketplace\Requests\PriceGuideBatchRequest;
use App\Marketplace\Requests\PriceGuideRequest;
use App\Marketplace\Resources\PriceGuideResource;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PriceGuideController extends Controller
{
    public function guide(PriceGuideRequest $request, GetPriceGuideAction $action): PriceGuideResource
    {
        $validated = $request->validated();

        return new PriceGuideResource($action->execute(
            (string) $validated['crop'],
            isset($validated['region']) ? (string) $validated['region'] : null,
        ));
    }

    public function batch(PriceGuideBatchRequest $request, GetPriceGuideAction $action): JsonResponse
    {
        $validated = $request->validated();
        $region = isset($validated['region']) ? (string) $validated['region'] : null;

        $guides = [];
        foreach ($validated['crops'] as $crop) {
            $guides[(string) $crop] = $action->execute((string) $crop, $region)->toArray();
        }

        return response()->json(['guides' => $guides]);
    }
}
