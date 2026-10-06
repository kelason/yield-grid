<?php

declare(strict_types=1);

namespace App\CropRecommendation\Controllers;

use App\CropRecommendation\Requests\CheckCompatibilityRequest;
use App\Domain\CropRecommendation\Actions\CheckCompatibilityAction;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CropCompatibilityController extends Controller
{
    public function check(CheckCompatibilityRequest $request, CheckCompatibilityAction $action): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $action->execute((string) $validated['crop_a'], (string) $validated['crop_b']),
        ]);
    }
}
