<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\DTOs;

final readonly class ScoreBreakdownData
{
    public function __construct(
        public int $plotActivity,
        public int $recommendationAdherence,
        public int $contractFulfillment,
        public int $offerReliability,
        public int $transactionVolume,
        public int $platformTenure,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'plot_activity' => $this->plotActivity,
            'recommendation_adherence' => $this->recommendationAdherence,
            'contract_fulfillment' => $this->contractFulfillment,
            'offer_reliability' => $this->offerReliability,
            'transaction_volume' => $this->transactionVolume,
            'platform_tenure' => $this->platformTenure,
        ];
    }

    /**
     * @param  array<string, mixed>  $scores
     */
    public static function fromArray(array $scores): self
    {
        return new self(
            plotActivity: (int) ($scores['plot_activity'] ?? 0),
            recommendationAdherence: (int) ($scores['recommendation_adherence'] ?? 0),
            contractFulfillment: (int) ($scores['contract_fulfillment'] ?? 0),
            offerReliability: (int) ($scores['offer_reliability'] ?? 0),
            transactionVolume: (int) ($scores['transaction_volume'] ?? 0),
            platformTenure: (int) ($scores['platform_tenure'] ?? 0),
        );
    }
}
