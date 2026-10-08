<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Resources\AdminOverviewResource;
use App\Constants\AdminConstants;
use App\Domain\Shared\Actions\GetAdminOverviewAction;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;

final class AdminOverviewController extends Controller
{
    public function __invoke(GetAdminOverviewAction $action): JsonResponse
    {
        $this->authorize('viewOverview', User::class);

        return (new AdminOverviewResource($action->execute()))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }
}
