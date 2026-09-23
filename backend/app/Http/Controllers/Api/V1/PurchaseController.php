<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Constants\PaymentConstants;
use App\Domain\Marketplace\Actions\ApproveCashPaymentAction;
use App\Domain\Marketplace\Actions\CancelCheckoutAction;
use App\Domain\Marketplace\Actions\CreateCashPurchaseAction;
use App\Domain\Marketplace\Actions\SplitPurchasableAction;
use App\Domain\Marketplace\Actions\VerifyPurchaseAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use App\Marketplace\Requests\ApproveCashPaymentRequest;
use App\Marketplace\Requests\CheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly VerifyPurchaseAction $verifyPurchaseAction,
        private readonly CancelCheckoutAction $cancelCheckoutAction,
        private readonly PayMongoService $payMongoService,
        private readonly CreateCashPurchaseAction $createCashPurchaseAction,
        private readonly ApproveCashPaymentAction $approveCashPaymentAction,
        private readonly SplitPurchasableAction $splitPurchasableAction
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'search' => $request->query('search'),
            'sort' => $request->query('sort'),
        ];

        $perPage = (int) $request->query('per_page', PaginationConstants::PURCHASES_PER_PAGE);

        $purchases = $this->purchaseRepository->getBuyerPurchases(
            $request->user()->id,
            $filters,
            $perPage
        );

        // Rely on webhooks or explicit user-initiated verification instead of looping API calls.
        // If fallback is absolutely necessary, dispatch a queued job to verify asynchronously.

        return PurchaseResource::collection($purchases);
    }

    public function show(Request $request, Purchase $purchase): PurchaseResource
    {
        if ($purchase->buyer_id !== $request->user()->id) {
            abort(HttpCode::FORBIDDEN, 'You do not own this purchase.');
        }

        $purchase->load('contract.farmer.farms');

        return new PurchaseResource($purchase);
    }

    public function checkout(CheckoutRequest $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validated();

        $purchasableClass = ($type === 'listing' || $type === 'listings') ? HarvestListing::class : ForwardContract::class;
        $purchasable = $purchasableClass::findOrFail($id);

        if (! $purchasable->is_purchasable) {
            return response()->json(['message' => 'Item is no longer available.'], HttpCode::CONFLICT);
        }

        if ($validated['payment_option'] === 'cash') {
            try {
                $purchase = $this->createCashPurchaseAction->execute($purchasable, $request->user()->id, (float) $validated['quantity_kg']);

                return response()->json([
                    'message' => 'Cash purchase request created. Please wait for farmer approval.',
                    'purchase_id' => $purchase->id,
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
            }
        }

        // PayMongo Flow
        $quantityKg = (float) $validated['quantity_kg'];

        $lockedPurchasable = DB::transaction(function () use ($purchasableClass, $id, $quantityKg) {
            $inner = $purchasableClass::where('id', $id)->lockForUpdate()->firstOrFail();
            if (! $inner->is_purchasable || $quantityKg > (float) $inner->quantity_kg) {
                return null;
            }

            $splitItem = $this->splitPurchasableAction->execute($inner, $quantityKg);
            $splitItem->status = ContractStatus::RESERVED;
            $splitItem->save();

            return $splitItem;
        });

        if (! $lockedPurchasable) {
            return response()->json(['message' => 'Item is no longer available or quantity insufficient.'], HttpCode::CONFLICT);
        }

        $totalContractAmount = $quantityKg * (float) $lockedPurchasable->price_per_kg;
        $isDownpayment = false;

        if ($lockedPurchasable instanceof ForwardContract) {
            $isDownpayment = $lockedPurchasable->estimated_harvest_date->isFuture();
        } else {
            $isDownpayment = ! $lockedPurchasable->is_harvest_available;
        }

        $amountPaid = $isDownpayment ? $totalContractAmount * PaymentConstants::DOWNPAYMENT_PERCENTAGE : $totalContractAmount;

        $purchaseData = [
            'buyer_id' => $request->user()->id,
            'quantity_kg' => $quantityKg,
            'amount_paid' => $amountPaid,
            'currency' => $lockedPurchasable->currency,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::GCASH, // PayMongo defaults to online methods
            'is_downpayment' => $isDownpayment,
            'total_contract_amount' => $totalContractAmount,
        ];

        if ($lockedPurchasable instanceof ForwardContract) {
            $purchaseData['forward_contract_id'] = $lockedPurchasable->id;
        } else {
            $purchaseData['harvest_listing_id'] = $lockedPurchasable->id;
        }

        $purchase = Purchase::create($purchaseData);

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $successUrl = $frontendUrl.'/checkout/success?session_id='.$purchase->id;
        $cancelUrl = $frontendUrl.'/checkout/cancel?session_id='.$purchase->id;

        try {
            // PayMongo service would need updating to support dynamic amount (amountPaid) rather than full contract price.
            // For now, we assume it can be modified.
            $checkoutData = $this->payMongoService->createCheckoutSession(
                contract: $lockedPurchasable,
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
                buyerId: $request->user()->id,
                customAmount: $amountPaid // We will need to update PayMongoService to accept this!
            );
        } catch (\Exception $e) {
            $lockedPurchasable->update(['status' => ContractStatus::AVAILABLE]);
            $purchase->delete();

            return response()->json(['message' => 'Payment gateway error.'], HttpCode::INTERNAL_SERVER_ERROR);
        }

        $purchase->update(['paymongo_checkout_id' => $checkoutData['checkout_id']]);

        return response()->json([
            'checkout_url' => $checkoutData['checkout_url'],
            'checkout_id' => $checkoutData['checkout_id'],
        ]);
    }

    public function cancelCheckout(Request $request, string $sessionId): JsonResponse
    {
        if (is_numeric($sessionId)) {
            $purchase = Purchase::where('id', $sessionId)
                ->where('buyer_id', $request->user()->id)
                ->where('payment_status', PaymentStatus::PENDING)
                ->first();
        } else {
            $purchase = $this->purchaseRepository->findPendingByCheckoutId($sessionId, $request->user()->id);
        }

        if ($purchase) {
            $this->cancelCheckoutAction->execute($purchase);
        }

        return response()->json(['message' => 'Checkout cancelled successfully.']);
    }

    public function verifyCheckout(Request $request, string $sessionId): JsonResponse
    {
        if (is_numeric($sessionId)) {
            $purchase = Purchase::where('id', $sessionId)
                ->where('buyer_id', $request->user()->id)
                ->firstOrFail();
        } else {
            $purchase = $this->purchaseRepository->findByCheckoutId($sessionId, $request->user()->id);
        }

        $purchase = $this->verifyPurchaseAction->execute($purchase);

        return response()->json(['status' => $purchase->payment_status->value]);
    }

    public function farmerPurchases(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', PaginationConstants::PURCHASES_PER_PAGE);

        $purchases = Purchase::with(['buyer', 'contract', 'harvestListing'])
            ->whereHas('contract', fn ($q) => $q->where('farmer_id', $request->user()->id))
            ->orWhereHas('harvestListing', fn ($q) => $q->where('farmer_id', $request->user()->id))
            ->latest('created_at')
            ->paginate($perPage);

        return PurchaseResource::collection($purchases);
    }

    public function approveCashPayment(ApproveCashPaymentRequest $request, Purchase $purchase): PurchaseResource
    {
        $validated = $request->validated();

        // Authorize farmer owns the contract/listing
        $purchasable = $purchase->contract ?? $purchase->harvestListing;
        if (! $purchasable || $purchasable->farmer_id !== $request->user()->id) {
            abort(HttpCode::FORBIDDEN, 'You do not own this purchase.');
        }

        $purchase = $this->approveCashPaymentAction->execute(
            $purchase,
            $validated['type'],
            isset($validated['amount']) ? (float) $validated['amount'] : null
        );

        return new PurchaseResource($purchase);
    }
}
