<?php

declare(strict_types=1);

namespace App\Community\Resources;

use App\Domain\Community\Models\ForumCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ForumCategory
 */
class ForumCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon_emoji' => $this->icon_emoji,
            'threads_count' => $this->whenCounted('threads'),
        ];
    }
}
