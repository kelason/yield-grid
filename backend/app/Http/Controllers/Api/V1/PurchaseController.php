<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
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
        private readonly PayMongoService $payMongoService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $purchases = Purchase::where('buyer_id', $request->user()->id)
            ->with(['contract.farmer.farms'])
            ->latest()
            ->paginate(15);

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

        $successUrl = config('app.frontend_url').'/checkout/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = config('app.frontend_url').'/checkout/cancel';

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
}
