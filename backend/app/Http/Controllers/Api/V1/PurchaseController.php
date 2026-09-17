<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
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
use Illuminate\Support\Facades\Log;

final class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly VerifyPurchaseAction $verifyPurchaseAction,
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

        // Auto-verify pending purchases as a fallback for missing webhooks/redirects
        foreach ($purchases as $purchase) {
            if ($purchase->payment_status === PaymentStatus::PENDING && $purchase->paymongo_checkout_id) {
                try {
                    $this->verifyPurchaseAction->execute($purchase);
                } catch (\Exception $e) {
                    Log::warning("Failed to auto-verify purchase {$purchase->id}: ".$e->getMessage());
                }
            }
        }

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

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $successUrl = $frontendUrl.'/checkout/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $frontendUrl.'/checkout/cancel?session_id={CHECKOUT_SESSION_ID}';

        try {
            // Create PayMongo checkout
            $checkoutData = $this->payMongoService->createCheckoutSession(
                contract: $lockedContract,
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
                buyerId: $request->user()->id
            );
        } catch (\Exception $e) {
            // Revert reservation if API call fails
            $lockedContract->update(['status' => ContractStatus::AVAILABLE]);
            throw $e;
        }

        // Create pending purchase
        $purchase = Purchase::create([
            'buyer_id' => $request->user()->id,
            'forward_contract_id' => $lockedContract->id,
            'paymongo_checkout_id' => $checkoutData['checkout_id'],
            'amount_paid' => $lockedContract->total_price,
            'currency' => $lockedContract->currency,
            'payment_status' => PaymentStatus::PENDING,
        ]);

        return response()->json([
            'checkout_url' => $checkoutData['checkout_url'],
            'checkout_id' => $checkoutData['checkout_id'],
        ]);
    }

    public function cancelCheckout(Request $request, string $sessionId): JsonResponse
    {
        $purchase = Purchase::where('paymongo_checkout_id', $sessionId)
            ->where('buyer_id', $request->user()->id)
            ->where('payment_status', PaymentStatus::PENDING)
            ->first();

        if ($purchase) {
            DB::transaction(function () use ($purchase) {
                $purchase->update(['payment_status' => PaymentStatus::FAILED]);
                ForwardContract::where('id', $purchase->forward_contract_id)
                    ->update(['status' => ContractStatus::AVAILABLE]);
            });
        }

        return response()->json(['message' => 'Checkout cancelled successfully.']);
    }

    public function verifyCheckout(Request $request, string $sessionId): JsonResponse
    {
        $purchase = Purchase::where('paymongo_checkout_id', $sessionId)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        $purchase = $this->verifyPurchaseAction->execute($purchase);

        return response()->json(['status' => $purchase->payment_status->value]);
    }
}
