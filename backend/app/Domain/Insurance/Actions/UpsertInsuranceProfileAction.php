<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Enums\RsbsaStatus;
use App\Domain\Insurance\Models\InsuranceProfile;
use Domain\Users\Models\User;

final class UpsertInsuranceProfileAction
{
    public function execute(User $user, ?string $rsbsaNumber, RsbsaStatus $status): InsuranceProfile
    {
        return InsuranceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'rsbsa_number' => $rsbsaNumber,
                'rsbsa_status' => $status,
            ],
        );
    }
}
