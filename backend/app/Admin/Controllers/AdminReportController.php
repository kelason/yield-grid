<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminReportFilterRequest;
use App\Admin\Requests\DecideContentReportRequest;
use App\Admin\Resources\AdminReportResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Shared\Actions\DecideContentReportAction;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

final class AdminReportController extends Controller
{
    public function __construct(
        private readonly ContentReportRepositoryInterface $reports,
        private readonly ContentTargetResolver $targets,
    ) {}

    public function index(AdminReportFilterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ContentReport::class);

        $validated = $request->validated();

        $reports = $this->reports->paginate(
            $this->repositoryFilters($validated),
            (int) ($validated['per_page'] ?? PaginationConstants::DEFAULT_PER_PAGE)
        );

        $this->preloadTargets($reports->getCollection());

        return AdminReportResource::collection($reports)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function show(int $report): JsonResponse
    {
        $found = $this->reports->findById($report);

        $this->authorize('view', $found);

        return $this->reportResponse($found);
    }

    public function decision(
        DecideContentReportRequest $request,
        int $report,
        DecideContentReportAction $action,
    ): JsonResponse {
        $found = $this->reports->findById($report);

        $this->authorize('decide', $found);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $decided = $action->execute(
                $actor,
                $found,
                ContentReportStatus::from((string) $request->validated('status')),
                $request->validated('outcome'),
                (string) $request->validated('note'),
                (int) $request->validated('expected_version'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->reportResponse($decided);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{status?: string, reportable_type?: string, reason?: string}
     */
    private function repositoryFilters(array $validated): array
    {
        $filters = [];

        foreach (['status' => 'status', 'type' => 'reportable_type', 'reason' => 'reason'] as $input => $column) {
            if (($validated[$input] ?? null) !== null) {
                $filters[$column] = (string) $validated[$input];
            }
        }

        return $filters;
    }

    /**
     * Batch-load current targets per type so the resource never issues one
     * query per row. Rows whose type or target is unknown keep a null marker.
     *
     * @param  Collection<int, ContentReport>  $reports
     */
    private function preloadTargets(Collection $reports): void
    {
        $grouped = [];

        foreach ($reports as $report) {
            $type = ReportTargetType::tryFrom((string) $report->getRawOriginal('reportable_type'));

            if ($type instanceof ReportTargetType) {
                $grouped[$type->value][] = (int) $report->reportable_id;
            }

            $report->setRelation('adminTarget', null);
        }

        foreach ($grouped as $value => $ids) {
            $loaded = $this->targets->findManyForAdmin(ReportTargetType::from($value), $ids)->keyBy('id');

            foreach ($reports as $report) {
                if ((string) $report->getRawOriginal('reportable_type') === $value && $loaded->has($report->reportable_id)) {
                    $report->setRelation('adminTarget', $loaded->get($report->reportable_id));
                }
            }
        }
    }

    private function reportResponse(ContentReport $report): JsonResponse
    {
        return (new AdminReportResource($report))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }
}
