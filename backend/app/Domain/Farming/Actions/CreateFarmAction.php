<?php

namespace Domain\Farming\Actions;

use Domain\Farming\DTOs\CreateFarmDTO;
use Domain\Farming\Models\Farm;

class CreateFarmAction
{
    public function __invoke(CreateFarmDTO $dto): Farm
    {
        return Farm::create([
            'user_id' => $dto->userId,
            'name' => $dto->name,
            'address' => $dto->address,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'total_area' => $dto->totalArea,
        ]);
    }
}
