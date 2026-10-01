<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropPriceSyncRun;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Infrastructure\Services\DaPriceSyncService;
use Illuminate\Console\Command;

final class SyncDaPricesCommand extends Command
{
    protected $signature = 'marketplace:sync-da-prices {--sample : Load explicit fictional demo rows instead of syncing (never used by default)}';

    protected $description = 'Fetch DA Bantay Presyo prices into the local reference table';

    public function handle(DaPriceSyncService $sync, CropReferencePriceRepositoryInterface $prices): int
    {
        if ($this->option('sample')) {
            $count = $prices->upsertRows($this->sampleRows());
            $this->info("Loaded {$count} sample price rows (source: manual, for demo only).");

            return self::SUCCESS;
        }

        $result = $sync->sync();

        if ($result->status === CropPriceSyncRun::STATUS_SUCCESS) {
            $this->info("DA price sync complete: {$result->rowsUpserted} rows upserted."
                .($result->usedAi ? ' (AI extraction used)' : ''));

            return self::SUCCESS;
        }

        $this->warn("DA price sync {$result->status}: {$result->error}");

        return self::FAILURE;
    }

    /**
     * Explicitly fictional demo rows. Only loaded via --sample, never by
     * default, and always labelled source=manual.
     *
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    private function sampleRows(): array
    {
        $today = now()->toDateString();

        return [
            ['crop_slug' => 'rice', 'crop_display_name' => 'Rice', 'tier' => PriceTier::RETAIL->value, 'price_per_kg' => 50.00, 'currency' => 'PHP', 'region_code' => null, 'market_name' => 'Sample Data (demo)', 'source' => PriceSource::MANUAL->value, 'observed_at' => $today],
            ['crop_slug' => 'rice', 'crop_display_name' => 'Rice', 'tier' => PriceTier::FARMGATE->value, 'price_per_kg' => 24.50, 'currency' => 'PHP', 'region_code' => null, 'market_name' => 'Sample Data (demo)', 'source' => PriceSource::MANUAL->value, 'observed_at' => $today],
            ['crop_slug' => 'corn', 'crop_display_name' => 'Corn', 'tier' => PriceTier::RETAIL->value, 'price_per_kg' => 32.00, 'currency' => 'PHP', 'region_code' => null, 'market_name' => 'Sample Data (demo)', 'source' => PriceSource::MANUAL->value, 'observed_at' => $today],
            ['crop_slug' => 'tomato', 'crop_display_name' => 'Tomato', 'tier' => PriceTier::RETAIL->value, 'price_per_kg' => 45.00, 'currency' => 'PHP', 'region_code' => null, 'market_name' => 'Sample Data (demo)', 'source' => PriceSource::MANUAL->value, 'observed_at' => $today],
            ['crop_slug' => 'onion', 'crop_display_name' => 'Onion', 'tier' => PriceTier::RETAIL->value, 'price_per_kg' => 120.00, 'currency' => 'PHP', 'region_code' => null, 'market_name' => 'Sample Data (demo)', 'source' => PriceSource::MANUAL->value, 'observed_at' => $today],
        ];
    }
}
