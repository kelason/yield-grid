<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Models\InsuranceEnrollment;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use LogicException;

final class CreateEnrollmentAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(User $user, array $validated): InsuranceEnrollment
    {
        if (isset($validated['plot_id'])) {
            $this->assertOwnPlot($user->id, (int) $validated['plot_id']);
        }

        return InsuranceEnrollment::create([
            'user_id' => $user->id,
            'plot_id' => $validated['plot_id'] ?? null,
            'program' => $validated['program'],
            'season' => $validated['season'],
            'season_year' => $validated['season_year'],
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    private function assertOwnPlot(int $userId, int $plotId): void
    {
        $owned = Plot::where('plots.id', $plotId)
            ->join('farms', 'farms.id', '=', 'plots.farm_id')
            ->where('farms.user_id', $userId)
            ->exists();

        if (! $owned) {
            throw new LogicException('The selected plot does not belong to the farmer.');
        }
    }
}
