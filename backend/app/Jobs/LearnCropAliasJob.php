<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Infrastructure\Services\CropNameAiNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class LearnCropAliasJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 60;

    public function __construct(
        private readonly string $slug,
        private readonly string $rawInput,
    ) {}

    public function uniqueId(): string
    {
        return 'learn-crop-alias:'.$this->slug;
    }

    public function handle(
        CropNameAiNormalizer $ai,
        CropReferencePriceRepositoryInterface $prices,
    ): void {
        if (strlen($this->slug) < MarketplaceConstants::PRICE_GUIDE_AI_LEARN_MIN_LENGTH
            || preg_match('/[a-z]/', $this->slug) !== 1) {
            return;
        }

        if ($prices->findAlias($this->slug) !== null) {
            return;
        }

        $catalog = $prices->cropCatalog();

        if (isset($catalog[$this->slug])) {
            return;
        }

        $result = $ai->normalize($this->rawInput, array_keys($catalog));

        if ($result['slug'] === null
            || $result['confidence'] < MarketplaceConstants::PRICE_GUIDE_AI_CONFIDENCE_MIN
            || ! isset($catalog[$result['slug']])) {
            return;
        }

        $prices->createAlias($this->slug, $result['slug'], CropPriceAlias::SOURCE_AI, false);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('LearnCropAliasJob failed', [
            'slug' => $this->slug,
            'error' => $e->getMessage(),
        ]);
    }
}
