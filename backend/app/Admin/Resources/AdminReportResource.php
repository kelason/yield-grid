<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Services\ContentTargetResolver;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentReport
 */
final class AdminReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = $this->resolveTarget();

        return [
            'id' => (string) $this->id,
            'reportable_type' => (string) $this->getRawOriginal('reportable_type'),
            'reportable_id' => (string) $this->reportable_id,
            'reason' => (string) $this->getRawOriginal('reason'),
            'description' => $this->description,
            'status' => $this->statusValue(),
            'version' => (int) $this->version,
            'outcome' => $this->outcome,
            'resolution_note' => $this->resolution_note,
            'reviewed_by' => $this->reviewed_by === null ? null : (string) $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'target_snapshot' => $this->target_snapshot,
            'reporter' => $this->reporterArray(),
            'target_available' => $target instanceof Model,
            'target' => $target instanceof Model ? new AdminContentResource($target) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Raw enum columns are read without triggering enum casts: never-valid
     * legacy rows (e.g. reportable_type "user") must render, never 500.
     */
    private function statusValue(): string
    {
        $raw = (string) $this->getRawOriginal('status');

        return ContentReportStatus::tryFrom($raw)->value ?? $raw;
    }

    /**
     * @return array{id: string, name: string, email: string}|null
     */
    private function reporterArray(): ?array
    {
        $reporter = $this->getRelationValue('reporter');

        if (! $reporter instanceof User) {
            return null;
        }

        return [
            'id' => (string) $reporter->id,
            'name' => (string) $reporter->name,
            'email' => (string) $reporter->email,
        ];
    }

    private function resolveTarget(): ?Model
    {
        if ($this->resource->relationLoaded('adminTarget')) {
            $preloaded = $this->resource->getRelation('adminTarget');

            return $preloaded instanceof Model ? $preloaded : null;
        }

        $type = ReportTargetType::tryFrom((string) $this->getRawOriginal('reportable_type'));

        if (! $type instanceof ReportTargetType) {
            return null;
        }

        return app(ContentTargetResolver::class)->findForAdmin($type, (string) $this->reportable_id);
    }
}
