<?php

declare(strict_types=1);

namespace App\Users\Resources;

use App\Domain\Community\Models\ForumThread;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'avatar_url' => $this->avatar_url,
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
