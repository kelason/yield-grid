<?php

declare(strict_types=1);

namespace App\Users\Controllers;

use App\Constants\PaginationConstants;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Shared\Controllers\Controller;
use App\Users\Resources\UserProfileResource;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Http\Request;

final class UserProfileController extends Controller
{
    public function __construct(
        private readonly ForwardContractRepositoryInterface $contractRepository,
        private readonly PurchaseRepositoryInterface $purchaseRepository,
    ) {}

    public function show(Request $request, User $user): UserProfileResource
    {
        $isOwnProfile = $request->user()?->id === $user->id;

        $postsQuery = ForumThread::where('user_id', $user->id)
            ->latest()
            ->limit(PaginationConstants::PROFILE_POSTS_LIMIT);

        if (! $isOwnProfile) {
            $postsQuery->where('is_anonymous', false);
        }

        $posts = $postsQuery->get();

        $stats = $user->role === UserRole::FARMER
            ? $this->contractRepository->getFarmerStats($user->id)
            : $this->purchaseRepository->getBuyerStats($user->id);

        return new UserProfileResource($user, $stats, $posts);
    }
}
