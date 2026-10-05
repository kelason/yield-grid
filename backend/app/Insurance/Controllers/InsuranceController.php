<?php

declare(strict_types=1);

namespace App\Insurance\Controllers;

use App\Constants\HttpCode;
use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Actions\AdvanceClaimStatusAction;
use App\Domain\Insurance\Actions\AdvanceEnrollmentStatusAction;
use App\Domain\Insurance\Actions\CreateClaimAction;
use App\Domain\Insurance\Actions\CreateEnrollmentAction;
use App\Domain\Insurance\Actions\GenerateEnrollmentPackAction;
use App\Domain\Insurance\Actions\RecordPolicyDetailsAction;
use App\Domain\Insurance\Actions\ResolveFarmerRegionAction;
use App\Domain\Insurance\Actions\UpsertInsuranceProfileAction;
use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Enums\RsbsaStatus;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceOffice;
use App\Domain\Insurance\Models\InsuranceProfile;
use App\Domain\Insurance\Models\InsuranceReminderLog;
use App\Insurance\Requests\AdvanceClaimStatusRequest;
use App\Insurance\Requests\AdvanceEnrollmentStatusRequest;
use App\Insurance\Requests\StoreClaimRequest;
use App\Insurance\Requests\StoreEnrollmentRequest;
use App\Insurance\Requests\UpdatePolicyDetailsRequest;
use App\Insurance\Requests\UpsertInsuranceProfileRequest;
use App\Insurance\Resources\InsuranceClaimResource;
use App\Insurance\Resources\InsuranceEnrollmentResource;
use App\Insurance\Resources\InsuranceOfficeResource;
use App\Insurance\Resources\InsuranceProfileResource;
use App\Insurance\Resources\InsuranceReminderResource;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InsuranceController extends Controller
{
    /**
     * Show the farmer's RSBSA insurance profile, creating it on first visit.
     */
    public function showProfile(): Response
    {
        $this->authorize('viewOwn', InsuranceProfile::class);

        $profile = InsuranceProfile::firstOrCreate(['user_id' => (int) auth()->id()]);

        return (new InsuranceProfileResource($profile))->response()->setStatusCode(HttpCode::OK);
    }

    /**
     * Create or update the farmer's RSBSA insurance profile.
     */
    public function updateProfile(
        UpsertInsuranceProfileRequest $request,
        UpsertInsuranceProfileAction $action,
    ): Response {
        $this->authorize('viewOwn', InsuranceProfile::class);

        $validated = $request->validated();

        $profile = $action->execute(
            $request->user(),
            $validated['rsbsa_number'] ?? null,
            RsbsaStatus::from($validated['rsbsa_status']),
        );

        return (new InsuranceProfileResource($profile))->response()->setStatusCode(HttpCode::OK);
    }

    /**
     * List the farmer's insurance enrollments, newest first.
     */
    public function indexEnrollments(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InsuranceEnrollment::class);

        $enrollments = InsuranceEnrollment::where('user_id', (int) auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return InsuranceEnrollmentResource::collection($enrollments);
    }

    /**
     * Start a new insurance enrollment for the farmer.
     */
    public function storeEnrollment(
        StoreEnrollmentRequest $request,
        CreateEnrollmentAction $action,
    ): Response|JsonResponse {
        $this->authorize('viewAny', InsuranceEnrollment::class);

        try {
            $enrollment = $action->execute($request->user(), $request->validated());
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return (new InsuranceEnrollmentResource($enrollment))->response()->setStatusCode(HttpCode::CREATED);
    }

    /**
     * Show a single insurance enrollment.
     */
    public function showEnrollment(InsuranceEnrollment $enrollment): InsuranceEnrollmentResource
    {
        $this->authorize('view', $enrollment);

        return new InsuranceEnrollmentResource($enrollment);
    }

    /**
     * Move an enrollment to its next lifecycle status.
     */
    public function advanceEnrollmentStatus(
        AdvanceEnrollmentStatusRequest $request,
        InsuranceEnrollment $enrollment,
        AdvanceEnrollmentStatusAction $action,
    ): InsuranceEnrollmentResource|JsonResponse {
        $this->authorize('manage', $enrollment);

        try {
            $updated = $action->execute($enrollment, EnrollmentStatus::from($request->validated()['status']));
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return new InsuranceEnrollmentResource($updated);
    }

    /**
     * Record the PCIC policy details (CIC number, coverage) on an enrollment.
     */
    public function recordPolicyDetails(
        UpdatePolicyDetailsRequest $request,
        InsuranceEnrollment $enrollment,
        RecordPolicyDetailsAction $action,
    ): InsuranceEnrollmentResource|JsonResponse {
        $this->authorize('manage', $enrollment);

        try {
            $updated = $action->execute($enrollment, $request->validated());
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return new InsuranceEnrollmentResource($updated);
    }

    /**
     * Queue generation of the enrollment pack PDF.
     */
    public function requestPack(
        InsuranceEnrollment $enrollment,
        GenerateEnrollmentPackAction $action,
    ): JsonResponse {
        $this->authorize('manage', $enrollment);

        $action->execute($enrollment);

        return response()->json(
            ['message' => 'Enrollment pack generation started.'],
            HttpCode::ACCEPTED
        );
    }

    /**
     * Download the ready enrollment pack PDF.
     */
    public function downloadPack(InsuranceEnrollment $enrollment): StreamedResponse|JsonResponse
    {
        $this->authorize('manage', $enrollment);

        if ($enrollment->pack_status !== PackStatus::READY || $enrollment->pack_path === null) {
            return response()->json(['message' => 'Enrollment pack is not ready yet.'], HttpCode::CONFLICT);
        }

        if ($enrollment->pack_expires_at !== null && $enrollment->pack_expires_at->isPast()) {
            return response()->json(['message' => 'Enrollment pack has expired.'], HttpCode::GONE);
        }

        return Storage::disk('local')->download($enrollment->pack_path, 'YieldGrid-PCIC-Enrollment-Pack.pdf');
    }

    /**
     * List the claims filed under an enrollment, newest loss first.
     */
    public function indexClaims(InsuranceEnrollment $enrollment): AnonymousResourceCollection
    {
        $this->authorize('view', $enrollment);

        $claims = $enrollment->claims()->orderByDesc('loss_date')->get();

        return InsuranceClaimResource::collection($claims);
    }

    /**
     * File a new claim under an enrollment.
     */
    public function storeClaim(
        StoreClaimRequest $request,
        InsuranceEnrollment $enrollment,
        CreateClaimAction $action,
    ): Response|JsonResponse {
        $this->authorize('manage', $enrollment);

        $claim = $action->execute($enrollment, $request->validated());

        return (new InsuranceClaimResource($claim))->response()->setStatusCode(HttpCode::CREATED);
    }

    /**
     * Move a claim to its next stage, recording the payout when paid.
     */
    public function advanceClaimStatus(
        AdvanceClaimStatusRequest $request,
        InsuranceClaim $claim,
        AdvanceClaimStatusAction $action,
    ): InsuranceClaimResource|JsonResponse {
        $this->authorize('manage', $claim);

        $validated = $request->validated();
        $to = ClaimStatus::from($validated['status']);

        try {
            $updated = $action->execute(
                $claim,
                $to,
                $to === ClaimStatus::PAID && isset($validated['paid_amount_php'])
                    ? (float) $validated['paid_amount_php']
                    : null,
            );
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return new InsuranceClaimResource($updated);
    }

    /**
     * List the farmer's recent insurance reminders.
     */
    public function indexReminders(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InsuranceEnrollment::class);

        $logs = InsuranceReminderLog::where('user_id', (int) auth()->id())
            ->orderByDesc('sent_at')
            ->limit(InsuranceConstants::REMINDERS_MAX_RESULTS)
            ->get();

        return InsuranceReminderResource::collection($logs);
    }

    /**
     * List PCIC offices, flagging the one serving the farmer's region.
     */
    public function indexOffices(ResolveFarmerRegionAction $action): JsonResponse
    {
        $this->authorize('viewAny', InsuranceEnrollment::class);

        $region = $action->execute(auth()->user());

        $offices = InsuranceOffice::orderByRaw('region_code NULLS FIRST')->orderBy('name')->get();

        $data = $offices->map(fn (InsuranceOffice $office): array => [
            ...(new InsuranceOfficeResource($office))->toArray(request()),
            'is_serving_region' => $office->region_code !== null && $office->region_code === $region,
        ]);

        return response()->json(['data' => $data->values()]);
    }
}
