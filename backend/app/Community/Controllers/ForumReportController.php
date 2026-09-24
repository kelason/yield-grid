<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\StoreReportRequest;
use App\Domain\Community\Actions\ReportContentAction;
use Illuminate\Http\JsonResponse;

class ForumReportController
{
    public function store(StoreReportRequest $request, ReportContentAction $action): JsonResponse
    {
        $action->execute(
            $request->user()->id,
            $request->validated('reportable_type'),
            (int) $request->validated('reportable_id'),
            $request->validated('reason'),
            $request->validated('description')
        );

        return response()->json(['message' => 'Report submitted successfully.']);
    }
}
