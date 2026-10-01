<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Infrastructure\Services\AiPriceEstimatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateAiPriceEstimateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 90;

    public function __construct(
        private readonly string $slug,
        private readonly string $rawInput,
    ) {}

    public function uniqueId(): string
    {
        return 'ai-price-estimate:'.$this->slug;
    }

    public function handle(
        AiPriceEstimatorService $estimator,
        CropReferencePriceRepositoryInterface $prices,
    ): void {
        if (! (bool) config('services.ai_estimates.enabled', true)) {
            return;
        }

        if (strlen($this->slug) < MarketplaceConstants::PRICE_GUIDE_AI_LEARN_MIN_LENGTH
            || preg_match('/[a-z]/', $this->slug) !== 1) {
            return;
        }

        $catalog = $prices->cropCatalog();
        $alias = $prices->findAlias($this->slug);

        // Unresolved inputs that already reach data through an alias need no
        // duplicate estimate. Resolved slugs (in the catalog themselves) are
        // estimated on their own rows even when an older alias shadows them.
        if ($alias !== null && ! isset($catalog[$this->slug]) && isset($catalog[$alias->crop_slug])) {
            return;
        }

        // The AI section shows alongside DA rows, so only a fresh same-day
        // AI estimate vetoes generation — DA rows must not.
        $rows = $prices->aiEstimatesBySlug($this->slug);
        $hasFreshAiEstimate = $rows->contains(fn (CropReferencePrice $row): bool => $row->observed_at->isToday());

        if ($hasFreshAiEstimate) {
            return;
        }

        $rows = $estimator->estimate($this->rawInput, $this->slug);

        if ($rows !== []) {
            $prices->upsertRows($rows);
        }
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('GenerateAiPriceEstimateJob failed', [
            'slug' => $this->slug,
            'error' => $e->getMessage(),
        ]);
    }
}
