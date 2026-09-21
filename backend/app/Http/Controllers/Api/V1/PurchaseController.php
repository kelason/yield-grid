<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Actions\CancelCheckoutAction;
use App\Domain\Marketplace\Actions\VerifyPurchaseAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Infrastructure\Marketplace\Services\PayMongoService;
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
        private readonly PayMongoService $payMongoService
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

    public function checkout(Request $request, ForwardContract $contract): JsonResponse
    {
        if (! $contract->is_purchasable) {
            return response()->json(['message' => 'Contract is no longer available.'], HttpCode::CONFLICT);
        }

        $lockedContract = DB::transaction(function () use ($contract) {
            // Lock for update to prevent concurrent purchases
            $innerContract = ForwardContract::where('id', $contract->id)->lockForUpdate()->firstOrFail();

            if (! $innerContract->is_purchasable) {
                return null;
            }

            // Reserve the contract
            $innerContract->update(['status' => ContractStatus::RESERVED]);

            return $innerContract;
        });

        if (! $lockedContract) {
            return response()->json(['message' => 'Contract is no longer available.'], HttpCode::CONFLICT);
        }

        // Create pending purchase FIRST so we can pass its ID in the URL
        $purchase = Purchase::create([
            'buyer_id' => $request->user()->id,
            'forward_contract_id' => $lockedContract->id,
            'amount_paid' => $lockedContract->total_price,
            'currency' => $lockedContract->currency,
            'payment_status' => PaymentStatus::PENDING,
        ]);

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        // PayMongo doesn't support template replacement, so we pass our internal purchase ID instead
        $successUrl = $frontendUrl.'/checkout/success?session_id='.$purchase->id;
        $cancelUrl = $frontendUrl.'/checkout/cancel?session_id='.$purchase->id;

        try {
            // Create PayMongo checkout
            $checkoutData = $this->payMongoService->createCheckoutSession(
                contract: $lockedContract,
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
                buyerId: $request->user()->id
            );
        } catch (\InvalidArgumentException $e) {
            // Revert reservation and delete purchase if amount exceeds limit
            $lockedContract->update(['status' => ContractStatus::AVAILABLE]);
            $purchase->delete();

            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            // Revert reservation and delete purchase if API call fails
            $lockedContract->update(['status' => ContractStatus::AVAILABLE]);
            $purchase->delete();

            return response()->json(['message' => 'Payment gateway error. Please try again later.'], HttpCode::INTERNAL_SERVER_ERROR);
        }

        $purchase->update([
            'paymongo_checkout_id' => $checkoutData['checkout_id'],
        ]);

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
}
