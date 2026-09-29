<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Marketplace\Models\CropDemandOffer;
use Domain\Users\Models\User;

final class CropDemandOfferPolicy
{
    public function view(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->farmer_id || $user->id === $offer->demand->buyer_id;
    }

    public function decide(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->demand->buyer_id;
    }

    public function withdraw(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->farmer_id;
    }

    public function cancel(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->farmer_id || $user->id === $offer->demand->buyer_id;
    }

    public function markDelivered(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->farmer_id;
    }

    public function settleBalance(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->farmer_id;
    }

    public function confirmCompleted(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->demand->buyer_id;
    }

    public function pay(User $user, CropDemandOffer $offer): bool
    {
        return $user->id === $offer->demand->buyer_id;
    }
}
