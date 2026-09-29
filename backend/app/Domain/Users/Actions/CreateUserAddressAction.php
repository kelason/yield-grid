<?php

declare(strict_types=1);

namespace Domain\Users\Actions;

use Domain\Users\DTOs\UpsertUserAddressDTO;
use Domain\Users\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class CreateUserAddressAction
{
    public function __invoke(UpsertUserAddressDTO $dto): UserAddress
    {
        return DB::transaction(function () use ($dto): UserAddress {
            $isFirst = ! UserAddress::where('user_id', $dto->userId)->exists();
            $isDefault = $dto->isDefault || $isFirst;

            if ($isDefault) {
                UserAddress::where('user_id', $dto->userId)->update(['is_default' => false]);
            }

            $address = UserAddress::create([
                'user_id' => $dto->userId,
                'label' => $dto->label,
                'region_code' => $dto->regionCode,
                'province_code' => $dto->provinceCode,
                'city_municipality_code' => $dto->cityMunicipalityCode,
                'barangay_code' => $dto->barangayCode,
                'street' => $dto->street,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'is_default' => $isDefault,
            ]);

            $this->syncLocationPoint($address);

            return $address->fresh() ?? $address;
        });
    }

    protected function syncLocationPoint(UserAddress $address): void
    {
        if ($address->latitude === null || $address->longitude === null) {
            DB::update('UPDATE user_addresses SET location = NULL WHERE id = ?', [$address->id]);

            return;
        }

        DB::update(
            'UPDATE user_addresses SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [(float) $address->longitude, (float) $address->latitude, $address->id]
        );
    }
}
