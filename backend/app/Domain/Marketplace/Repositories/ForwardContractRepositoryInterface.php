<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Repositories;

use App\Domain\Marketplace\Models\ForwardContract;

interface ForwardContractRepositoryInterface
{
    public function create(array $data): ForwardContract;
}
