<?php

declare(strict_types=1);

namespace App\CropRecommendation\Controllers;

use App\Constants\HttpCode;
use App\Constants\LocaleConstants;
use App\CropRecommendation\Requests\AnalyzePlotRequest;
use App\CropRecommendation\Resources\CropRecommendationResource;
use App\Domain\CropRecommendation\DTOs\AnalysisPreferences;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\Services\ReverseGeocodeService;
use App\Shared\Controllers\Controller;
use Domain\Farming\Models\Plot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CropRecommendationController extends Controller
{
    public function __construct(
        private readonly ReverseGeocodeService $geocoding
    ) {}

    public function analyze(AnalyzePlotRequest $request, Plot $plot): JsonResponse
    {
        Gate::authorize('analyze', $plot);

        $lockKey = "analyzing_plot_{$plot->id}";

        // Prevent spamming analysis jobs concurrently on the same plot within 15 seconds
        if (! Cache::add($lockKey, true, 15)) {
            return response()->json([
                'message' => 'An analysis is already underway for this plot. Please wait a moment.',
            ], HttpCode::TOO_MANY_REQUESTS);
        }

        $preferences = AnalysisPreferences::fromArray($request->validated());

        AnalyzePlotJob::dispatch(
            $plot->id,
            $preferences->isEmpty() ? null : $preferences->toArray(),
            $request->user()->locale ?? LocaleConstants::DEFAULT
        );

        return response()->json([
            'message' => 'Analysis started.',
        ], HttpCode::ACCEPTED);
    }

    public function index(Plot $plot): JsonResponse
    {
        Gate::authorize('view', $plot);

        $recommendations = CropRecommendation::where('plot_id', $plot->id)
            ->with('plot:id,farm_id,verification_status', 'plot.farm:id,verification_status')
            ->latest()
            ->take(10)
            ->get();
        $location = $this->geocoding->resolvePlotLocation($plot);

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
        Gate::authorize('update', $recommendation);

        $validated = $request->validate([
            'status' => ['required', Rule::in([RecommendationStatus::ACCEPTED->value, RecommendationStatus::REJECTED->value])],
        ]);

        $status = RecommendationStatus::tryFrom($validated['status']);

        $recommendation->update([
            'status' => $status,
        ]);

        return response()->json(new CropRecommendationResource($recommendation));
    }
}
