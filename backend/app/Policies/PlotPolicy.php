<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;

class PlotPolicy
{
    public function view(User $user, Plot $plot): bool
    {
        return $plot->farm && (int) $plot->farm->user_id === (int) $user->id;
    }

    public function analyze(User $user, Plot $plot): bool
    {
        return $plot->farm && (int) $plot->farm->user_id === (int) $user->id;
    }

    public function update(User $user, Plot $plot): bool
    {
        return $plot->farm && (int) $plot->farm->user_id === (int) $user->id;
    }

    public function delete(User $user, Plot $plot): bool
    {
        return $plot->farm && (int) $plot->farm->user_id === (int) $user->id;
    }
}
