<?php

declare(strict_types=1);

namespace App\Users\Resources;

use App\Domain\Community\Models\ForumThread;
use App\Infrastructure\Services\PsgcService;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin User
 */
class UserProfileResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $stats
     * @param  Collection<int, ForumThread>  $posts
     */
    public function __construct(User $user, private readonly array $stats, private readonly Collection $posts)
    {
        parent::__construct($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwnProfile = $request->user()?->id === $this->id;
        $defaultAddress = $this->defaultAddress();

        $locationSummary = null;
        if ($defaultAddress !== null) {
            /** @var PsgcService $psgc */
            $psgc = app(PsgcService::class);
            $names = $psgc->resolveNames([
                'region_code' => $defaultAddress->region_code,
                'province_code' => $defaultAddress->province_code,
                'city_municipality_code' => $defaultAddress->city_municipality_code,
                'barangay_code' => $defaultAddress->barangay_code,
            ]);
            $locationParts = array_filter([$names['city_municipality'], $names['province']]);
            $locationSummary = $locationParts === [] ? null : implode(', ', $locationParts);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'avatar_url' => $this->avatar_url,
            'location_summary' => $locationSummary,
            'addresses' => $isOwnProfile
                ? UserAddressResource::collection($this->addresses()->orderByDesc('is_default')->oldest()->get())
                : null,
            'stats' => $this->role === UserRole::FARMER
                ? [
                    'total_listed' => (int) $this->stats['total_listed'],
                    'total_sold' => (int) $this->stats['total_sold'],
                    'total_reserved' => (int) $this->stats['total_reserved'],
                    'total_revenue' => (float) $this->stats['total_revenue'],
                ]
                : [
                    'total_purchases' => (int) $this->stats['total_purchases'],
                    'total_spent' => (float) $this->stats['total_spent'],
                ],
            'posts' => $this->posts->map(fn (ForumThread $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'vote_score' => $post->vote_score,
                'reply_count' => $post->reply_count,
                'created_at' => $post->created_at,
            ])->values(),
        ];
    }
}
