<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use Illuminate\Database\Eloquent\Model;

final class SplitPurchasableAction
{
    /**
     * Splits a purchasable item (ForwardContract or HarvestListing) if the requested quantity is less than the total.
     * Returns the model that should be tied to the purchase (either the original if full, or a new clone if partial).
     */
    public function execute(Model $purchasable, float $requestedQuantityKg): Model
    {
        if ($requestedQuantityKg >= (float) $purchasable->quantity_kg) {
            // Full purchase, no splitting required
            return $purchasable;
        }

        $remainingQuantity = (float) $purchasable->quantity_kg - $requestedQuantityKg;
        $pricePerKg = (float) $purchasable->price_per_kg;

        // Clone the purchasable for the buyer's purchase
        $purchasedItem = $purchasable->replicate();
        $purchasedItem->quantity_kg = $requestedQuantityKg;
        $purchasedItem->total_price = $requestedQuantityKg * $pricePerKg;
        // Don't save yet, let the caller handle status changes and saving

        // Update the original purchasable's inventory
        $purchasable->quantity_kg = $remainingQuantity;
        $purchasable->total_price = $remainingQuantity * $pricePerKg;
        $purchasable->save();

        $purchasedItem->save();

        return $purchasedItem;
    }
}
