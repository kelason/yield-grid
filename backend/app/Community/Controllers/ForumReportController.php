<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\StoreReportRequest;
use App\Constants\HttpCode;
use App\Domain\Community\Actions\ReportContentAction;
use App\Domain\Shared\Models\ContentReport;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ForumReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportContentAction $action): JsonResponse
    {
        $this->authorize('create', ContentReport::class);

        try {
            $action->execute(
                $request->user()->id,
                (string) $request->validated('reportable_type'),
                (int) $request->validated('reportable_id'),
                (string) $request->validated('reason'),
                $request->validated('description'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Report submitted successfully.']);
    }
}
