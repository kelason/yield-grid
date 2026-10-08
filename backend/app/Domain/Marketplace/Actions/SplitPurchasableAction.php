<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use InvalidArgumentException;
use LogicException;

final class SplitPurchasableAction
{
    public function __construct(
        private readonly ForwardContractRepositoryInterface $contracts,
    ) {}

    /**
     * Splits a purchasable item (ForwardContract or HarvestListing) if the requested quantity is less than the total.
     * Returns the model that should be tied to the purchase (either the original if full, or a new clone if partial).
     */
    public function execute(ForwardContract|HarvestListing $purchasable, float $requestedQuantityKg): ForwardContract|HarvestListing
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
        // The clone inherits the original moderation root and never copies
        // hidden flags: effective visibility always derives from the root.
        $purchasedItem->moderation_root_id = $purchasable->moderation_root_id ?? $purchasable->id;
        $purchasedItem->hidden_at = null;
        $purchasedItem->hidden_by = null;
        $purchasedItem->hidden_reason = null;
        // Don't save yet, let the caller handle status changes and saving

        // Update the original purchasable's inventory
        $purchasable->quantity_kg = $remainingQuantity;
        $purchasable->total_price = $remainingQuantity * $pricePerKg;
        $purchasable->save();

        $purchasedItem->save();

        return $purchasedItem;
    }

    /**
     * Lock a purchasable item for checkout in root-before-item order and
     * recheck effective visibility under those locks. The locked root is
     * attached to the returned item so later checks cannot race. Requires an
     * enclosing transaction.
     *
     * @param  class-string<ForwardContract|HarvestListing>  $purchasableClass
     */
    public function lockVisible(string $purchasableClass, int $id): ForwardContract|HarvestListing
    {
        if ($purchasableClass === ForwardContract::class) {
            $root = $this->contracts->findModerationRootLocked($id);
            $item = $this->contracts->findByIdLocked($id);
        } elseif ($purchasableClass === HarvestListing::class) {
            $root = HarvestListing::findModerationRootLocked($id);
            $item = HarvestListing::whereKey($id)->lockForUpdate()->firstOrFail();
        } else {
            throw new InvalidArgumentException('Only forward contracts and harvest listings can be checked out.');
        }

        $item->setRelation('moderationRoot', $root);

        if ($item->isEffectivelyHidden()) {
            throw new LogicException('Item is no longer available.');
        }

        return $item;
    }
}
