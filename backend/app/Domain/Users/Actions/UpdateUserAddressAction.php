<?php

declare(strict_types=1);

namespace Domain\Users\Actions;

use Domain\Users\DTOs\UpsertUserAddressDTO;
use Domain\Users\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UpdateUserAddressAction extends CreateUserAddressAction
{
    public function execute(UserAddress $address, UpsertUserAddressDTO $dto): UserAddress
    {
        return DB::transaction(function () use ($address, $dto): UserAddress {
            if ($dto->isDefault) {
                UserAddress::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update([
                'label' => $dto->label,
                'region_code' => $dto->regionCode,
                'province_code' => $dto->provinceCode,
                'city_municipality_code' => $dto->cityMunicipalityCode,
                'barangay_code' => $dto->barangayCode,
                'street' => $dto->street,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'is_default' => $dto->isDefault ? true : $address->is_default,
            ]);

            $this->syncLocationPoint($address->fresh() ?? $address);

            return $address->fresh() ?? $address;
        });
    }
}
