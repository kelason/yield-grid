<?php

declare(strict_types=1);

namespace App\Shared\Controllers;

use App\Constants\HttpCode;
use App\Domain\Shared\Actions\SubmitContentReportAction;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Shared\Requests\StoreContentReportRequest;
use App\Shared\Resources\ContentReportReceiptResource;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class ContentReportController extends Controller
{
    public function store(StoreContentReportRequest $request, SubmitContentReportAction $action): JsonResponse
    {
        $this->authorize('create', ContentReport::class);

        /** @var User $reporter */
        $reporter = $request->user();

        try {
            $report = $action->execute(
                $reporter,
                ReportTargetType::from((string) $request->validated('reportable_type')),
                (string) $request->validated('reportable_id'),
                ContentReportReason::from((string) $request->validated('reason')),
                $request->validated('description'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        }

        $status = $report->wasRecentlyCreated ? HttpCode::CREATED : HttpCode::OK;

        return (new ContentReportReceiptResource($report))->response()->setStatusCode($status);
    }
}
