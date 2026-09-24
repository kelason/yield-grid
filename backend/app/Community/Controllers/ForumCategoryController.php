<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Resources\ForumCategoryResource;
use App\Domain\Community\Models\ForumCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ForumCategoryController
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ForumCategory::withCount('threads')
            ->orderBy('sort_order')
            ->get();

        return ForumCategoryResource::collection($categories);
    }
}
