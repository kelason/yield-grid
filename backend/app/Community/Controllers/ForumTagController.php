<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Resources\ForumTagResource;
use App\Domain\Community\Models\ForumTag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ForumTagController
{
    public function index(): AnonymousResourceCollection
    {
        $tags = ForumTag::orderBy('name')->get();

        return ForumTagResource::collection($tags);
    }
}
