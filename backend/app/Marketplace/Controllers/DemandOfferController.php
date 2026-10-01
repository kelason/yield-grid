<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Actions\CancelDemandOfferAction;
use App\Domain\Marketplace\Actions\ConfirmOfferCompletedAction;
use App\Domain\Marketplace\Actions\DecideDemandOfferAction;
use App\Domain\Marketplace\Actions\MarkOfferDeliveredAction;
use App\Domain\Marketplace\Actions\SettleOfferBalanceAction;
use App\Domain\Marketplace\Actions\SubmitDemandOfferAction;
use App\Domain\Marketplace\Actions\WithdrawDemandOfferAction;
use App\Domain\Marketplace\DTOs\SubmitDemandOfferDTO;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Marketplace\Requests\SubmitOfferRequest;
use App\Marketplace\Resources\CropDemandOfferResource;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

final class DemandOfferController extends Controller
{
    public function indexForDemand(Request $request, CropDemand $demand): AnonymousResourceCollection
    {
        $this->authorize('decideOffer', $demand);

        $offers = $demand->offers()->with(['farmer', 'purchase'])->orderByDesc('created_at')->get();

        return CropDemandOfferResource::collection($offers);
    }

    public function myOffers(Request $request): AnonymousResourceCollection
    {
        $statuses = collect(explode(',', (string) $request->query('status', '')))
            ->map(fn (string $status): ?string => DemandOfferStatus::tryFrom(trim($status))?->value)
            ->filter()
            ->values()
            ->all();

        $offers = CropDemandOffer::where('farmer_id', $request->user()->id)
            ->with(['farmer', 'demand.buyer', 'demand.deliveryAddress', 'purchase'])
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', PaginationConstants::MARKETPLACE_PER_PAGE));

        return CropDemandOfferResource::collection($offers);
    }

    public function show(Request $request, CropDemandOffer $offer): CropDemandOfferResource
    {
        $this->authorize('view', $offer);

        $offer->load(['farmer', 'demand.buyer', 'demand.deliveryAddress', 'purchase']);

        return new CropDemandOfferResource($offer);
    }

    public function store(SubmitOfferRequest $request, CropDemand $demand, SubmitDemandOfferAction $action): JsonResponse
    {
        $this->authorize('submitOffer', $demand);

        try {
            $offer = $action->execute(SubmitDemandOfferDTO::fromRequest(
                $demand->id,
                $request->user()->id,
                $request->validated()
            ));
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return (new CropDemandOfferResource($offer))->response()->setStatusCode(HttpCode::CREATED);
    }

    public function accept(Request $request, CropDemandOffer $offer, DecideDemandOfferAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('decide', $offer);

        try {
            $offer = $action->accept($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }

    public function reject(Request $request, CropDemandOffer $offer, DecideDemandOfferAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('decide', $offer);

        try {
            $offer = $action->reject($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }

    public function withdraw(Request $request, CropDemandOffer $offer, WithdrawDemandOfferAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('withdraw', $offer);

        try {
            $offer = $action->execute($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }

    public function cancel(Request $request, CropDemandOffer $offer, CancelDemandOfferAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('cancel', $offer);

        try {
            $offer = $action->execute($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }

    public function markDelivered(Request $request, CropDemandOffer $offer, MarkOfferDeliveredAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('markDelivered', $offer);

        try {
            $offer = $action->execute($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }

    public function settleBalance(Request $request, CropDemandOffer $offer, SettleOfferBalanceAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('settleBalance', $offer);

        try {
            $offer = $action->execute($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand', 'purchase']);

        return new CropDemandOfferResource($offer);
    }

    public function confirmCompleted(Request $request, CropDemandOffer $offer, ConfirmOfferCompletedAction $action): CropDemandOfferResource|JsonResponse
    {
        $this->authorize('confirmCompleted', $offer);

        try {
            $offer = $action->execute($offer);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $offer->load(['farmer', 'demand']);

        return new CropDemandOfferResource($offer);
    }
}
