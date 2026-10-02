<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Actions;

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class GatherCreditScoreMetricsAction
{
    /**
     * Gather all raw metrics from across bounded contexts using efficient aggregate queries.
     *
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');
        $plotIds = Plot::whereIn('farm_id', $farmIds)->pluck('id');

        return array_merge(
            $this->gatherFarmingMetrics($farmIds),
            $this->gatherRecommendationMetrics($user->id, $plotIds),
            $this->gatherContractMetrics($user->id),
            $this->gatherOfferMetrics($user->id),
            $this->gatherOfferOutcomeMetrics($user->id),
            $this->gatherVolumeMetrics($user->id),
            $this->gatherTenureMetrics($user),
        );
    }

    /**
     * @param  Collection<int, int>  $farmIds
     * @return array<string, mixed>
     */
    private function gatherFarmingMetrics(Collection $farmIds): array
    {
        $plots = Plot::whereIn('farm_id', $farmIds);
        $activePlots = $plots->count();
        $plotsWithPolygon = (clone $plots)->whereNotNull('polygon')->count();
        $plotsWithSoilType = (clone $plots)->whereNotNull('soil_type')->count();

        return [
            'active_plots' => $activePlots,
            'plots_with_polygon' => $plotsWithPolygon,
            'plots_with_soil_type' => $plotsWithSoilType,
        ];
    }

    /**
     * @param  Collection<int, int>  $plotIds
     * @return array<string, mixed>
     */
    private function gatherRecommendationMetrics(int $userId, Collection $plotIds): array
    {
        $totalRecommendations = CropRecommendation::whereIn('plot_id', $plotIds)->count();
        $acceptedRecommendations = CropRecommendation::whereIn('plot_id', $plotIds)
            ->where('status', RecommendationStatus::ACCEPTED)
            ->count();
        $publishedToContract = ForwardContract::where('farmer_id', $userId)
            ->whereNotNull('crop_recommendation_id')
            ->count();

        return [
            'has_recommendations' => $totalRecommendations > 0,
            'total_recommendations' => $totalRecommendations,
            'accepted_recommendations' => $acceptedRecommendations,
            'published_to_contract' => $publishedToContract,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherContractMetrics(int $userId): array
    {
        $totalContracts = ForwardContract::where('farmer_id', $userId)->count();
        $soldContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::SOLD)
            ->count();
        $cancelledContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::CANCELLED)
            ->count();
        $expiredContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::EXPIRED)
            ->count();

        return [
            'total_contracts' => $totalContracts,
            'sold_contracts' => $soldContracts,
            'cancelled_contracts' => $cancelledContracts,
            'expired_contracts' => $expiredContracts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherOfferMetrics(int $userId): array
    {
        $totalOffers = CropDemandOffer::where('farmer_id', $userId)->count();
        $acceptedOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::ACCEPTED)
            ->count();
        $completedOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::COMPLETED)
            ->count();
        $withdrawnOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::WITHDRAWN)
            ->count();

        return [
            'total_offers' => $totalOffers,
            'accepted_offers' => $acceptedOffers,
            'completed_offers' => $completedOffers,
            'withdrawn_offers' => $withdrawnOffers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherOfferOutcomeMetrics(int $userId): array
    {
        $cancelledOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::CANCELLED)
            ->count();
        $deliveredOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::DELIVERED)
            ->count();
        // Offers that reached a "successful" lifecycle stage (accepted+)
        $offersWithDelivery = CropDemandOffer::where('farmer_id', $userId)
            ->whereIn('status', [
                DemandOfferStatus::DELIVERED,
                DemandOfferStatus::COMPLETED,
            ])
            ->count();

        return [
            'cancelled_offers' => $cancelledOffers,
            'delivered_offers' => $deliveredOffers,
            'offers_with_delivery' => $offersWithDelivery,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherVolumeMetrics(int $userId): array
    {
        // Transaction volume — purchases where this farmer was the seller.
        // The seller-channel conditions are grouped so payment_status applies to every channel.
        $farmerPurchases = Purchase::where('payment_status', PaymentStatus::COMPLETED)
            ->where(function (Builder $query) use ($userId): void {
                $query->whereHas('contract', fn ($q) => $q->where('farmer_id', $userId))
                    ->orWhereHas('harvestListing', fn ($q) => $q->where('farmer_id', $userId))
                    ->orWhereHas('demandOffer', fn ($q) => $q->where('farmer_id', $userId));
            });

        $totalTransactionValue = (float) (clone $farmerPurchases)->sum('amount_paid');
        $transactionCount = (clone $farmerPurchases)->count();

        return [
            'total_transaction_value' => $totalTransactionValue,
            'transaction_count' => $transactionCount,
            'active_months' => $this->countActiveMonths($farmerPurchases),
        ];
    }

    /**
     * Active months: months where at least one purchase was paid.
     *
     * @param  Builder<Purchase>  $purchases
     */
    private function countActiveMonths(Builder $purchases): int
    {
        if ((clone $purchases)->min('purchased_at') === null) {
            return 0;
        }

        return (int) (clone $purchases)
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM purchased_at) * 12 + EXTRACT(MONTH FROM purchased_at) AS ym')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherTenureMetrics(User $user): array
    {
        $now = Carbon::now();

        return [
            'account_age_days' => (int) $user->created_at->diffInDays($now),
            'account_age_months' => max(1, (int) $user->created_at->diffInMonths($now)),
            'email_verified' => $user->hasVerifiedEmail(),
            'has_phone' => $user->phone !== null && $user->phone !== '',
            'has_address' => $user->addresses()->exists(),
            'has_avatar' => $user->avatar_url !== null && $user->avatar_url !== '',
        ];
    }
}
