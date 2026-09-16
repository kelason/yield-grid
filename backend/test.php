<?php

use App\Domain\CropRecommendation\Models\CropRecommendation;
use App\Domain\Marketplace\Actions\PublishContractAction;
use App\Http\Controllers\Api\V1\ForwardContractController;
use App\Http\Requests\PublishContractRequest;
use Domain\Users\Models\User;
use Illuminate\Validation\ValidationException;

$user = User::first();
$recommendation = CropRecommendation::find(3);
$request = PublishContractRequest::create('/api/v1/recommendations/3/publish', 'POST', [
    'title' => 'Sweet Potato (Camote) — Forward Contract',
    'description' => '',
    'quantity_kg' => 3350,
    'price_per_kg' => 50.00,
    'estimated_harvest_date' => '2026-12-15',
    'expiry_date' => '2026-11-30',
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $controller = app()->make(ForwardContractController::class);
    $action = app()->make(PublishContractAction::class);

    // We need to resolve the form request properly to trigger validation
    app()->instance('request', $request);
    $resolvedRequest = app()->make(PublishContractRequest::class);

    $controller->store($resolvedRequest, $recommendation, $action);
    echo "Success\n";
} catch (ValidationException $e) {
    echo "Validation Failed:\n";
    echo json_encode($e->errors(), JSON_PRETTY_PRINT);
    echo "\n";
} catch (Exception $e) {
    echo 'Other Exception: '.$e->getMessage()."\n";
}
