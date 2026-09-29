<?php

declare(strict_types=1);

namespace Domain\Users\Actions;

use App\Domain\Marketplace\Models\CropDemand;
use Domain\Users\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use LogicException;

class DeleteUserAddressAction
{
    public function execute(UserAddress $address): void
    {
        if (CropDemand::where('address_id', $address->id)->exists()) {
            throw new LogicException('This address is used as a delivery address and cannot be deleted.');
        }

        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $userId = $address->user_id;
            $address->delete();

            if ($wasDefault) {
                $next = UserAddress::where('user_id', $userId)->oldest()->first();
                if ($next !== null) {
                    $next->update(['is_default' => true]);
                }
            }
        });
    }
}
