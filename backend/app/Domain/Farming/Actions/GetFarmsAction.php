<?php

namespace Domain\Farming\Actions;

use Domain\Farming\Models\Farm;
use Illuminate\Database\Eloquent\Collection;

class GetFarmsAction
{
    /**
     * @return Collection<int, Farm>
     */
    public function __invoke(int $userId): Collection
    {
        return Farm::where('user_id', $userId)
            ->withCount('plots')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
