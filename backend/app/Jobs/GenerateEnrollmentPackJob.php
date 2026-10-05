<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Events\EnrollmentPackGenerated;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Services\EnrollmentPackGeneratorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateEnrollmentPackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = InsuranceConstants::PACK_GENERATION_TIMEOUT_SECONDS;

    public function __construct(
        private readonly int $enrollmentId,
    ) {}

    public function handle(EnrollmentPackGeneratorInterface $packService): void
    {
        $enrollment = InsuranceEnrollment::with('user')->findOrFail($this->enrollmentId);

        try {
            $packPath = $packService->generateEnrollmentPack($enrollment);

            $enrollment->update([
                'pack_status' => PackStatus::READY,
                'pack_path' => $packPath,
                'pack_generated_at' => now(),
            ]);

            EnrollmentPackGenerated::dispatch($enrollment);
        } catch (\Throwable $e) {
            $enrollment->update(['pack_status' => PackStatus::FAILED]);

            throw $e;
        }
    }
}
