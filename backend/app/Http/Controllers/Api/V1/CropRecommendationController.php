<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use App\Domain\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Plot;
use App\Http\Controllers\Controller;
use App\Http\Resources\CropRecommendationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class CropRecommendationController extends Controller
{
    public function analyze(Request $request, Plot $plot): JsonResponse
    {
        $lockKey = "analyzing_plot_{$plot->id}";

        // Prevent spamming analysis jobs concurrently on the same plot within 15 seconds
        if (!Cache::add($lockKey, true, 15)) {
            return response()->json([
                'message' => 'An analysis is already underway for this plot. Please wait a moment.',
            ], 429);
        }

        AnalyzePlotJob::dispatch($plot->id);

        return response()->json([
            'message' => 'Analysis started.',
        ], 202);
    }

    public function index(Plot $plot): JsonResponse
    {
        $recommendations = CropRecommendation::where('plot_id', $plot->id)
            ->latest()
            ->take(10)
            ->get();
        $location = $plot->resolveLocation();

        return response()->json([
            'data' => CropRecommendationResource::collection($recommendations),
            'meta' => [
                'plot_id' => $plot->id,
                'plot_name' => $plot->name,
                'calculated_area' => (float) ($plot->calculated_area ?? 0),
                'soil_type' => $plot->soil_type instanceof \BackedEnum ? $plot->soil_type->value : (string) ($plot->soil_type ?? ''),
                'city' => $location['city'],
                'state' => $location['state'],
                'country' => $location['country'],
            ],
        ]);
    }

    public function updateStatus(Request $request, CropRecommendation $recommendation): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);
        
        $status = RecommendationStatus::tryFrom($validated['status']);
        
        $recommendation->update([
            'status' => $status,
        ]);

        return response()->json(new CropRecommendationResource($recommendation));
    }
}
