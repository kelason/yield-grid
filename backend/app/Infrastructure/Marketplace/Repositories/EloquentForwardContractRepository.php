<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;

class EloquentForwardContractRepository implements ForwardContractRepositoryInterface
{
    public function create(array $data): ForwardContract
    {
        return ForwardContract::create($data);
    }
}
