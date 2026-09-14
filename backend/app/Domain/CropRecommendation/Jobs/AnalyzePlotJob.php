<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Jobs;

use App\Domain\CropRecommendation\Events\AnalysisCompleted;
use App\Domain\CropRecommendation\Models\CropRecommendation;
use App\Domain\CropRecommendation\Services\AgroMonitoringService;
use App\Domain\CropRecommendation\Services\CropAdvisorService;
use Domain\Farming\Models\Plot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzePlotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $plotId;

    public function __construct(int $plotId)
    {
        $this->plotId = $plotId;
    }

    public function handle(AgroMonitoringService $agroService, CropAdvisorService $advisorService): void
    {
        try {
            $plot = Plot::findOrFail($this->plotId);

            // 1. Fetch AgroMonitoring Data (Weather & Soil)
            $agroData = $agroService->getPlotData($plot);
            if (! $agroData || ! isset($agroData['weather']) || ! isset($agroData['soil'])) {
                throw new \Exception('Could not fetch AgroMonitoring data');
            }

            // 2. Get AI Recommendations
            $recommendations = $advisorService->getRecommendations($plot, $agroData);

            // 3. Save to Database (remove previous pending recommendations so fresh analysis is displayed)
            CropRecommendation::where('plot_id', $plot->id)
                ->where('status', 'pending')
                ->delete();

            foreach ($recommendations as $rec) {
                CropRecommendation::create([
                    'plot_id' => $plot->id,
                    'crop_name' => $rec['crop_name'] ?? 'Unknown',
                    'confidence_score' => $rec['confidence_score'] ?? 0,
                    'reasoning' => $rec['reasoning'] ?? '',
                    'projected_yield' => $rec['projected_yield'] ?? '',
                    'status' => 'pending',
                ]);
            }

            // 4. Broadcast Event
            event(new AnalysisCompleted($plot->id));

        } catch (\Exception $e) {
            Log::error('AnalyzePlotJob Failed: '.$e->getMessage());
            $this->fail($e);
        }
    }
}
