<?php
$user = Domain\Users\Models\User::first();
$recommendation = App\Domain\CropRecommendation\Models\CropRecommendation::find(3);
$request = App\Http\Requests\PublishContractRequest::create('/api/v1/recommendations/3/publish', 'POST', [
    'title' => 'Sweet Potato (Camote) — Forward Contract',
    'description' => '',
    'quantity_kg' => 3350,
    'price_per_kg' => 50.00,
    'estimated_harvest_date' => '2026-12-15',
    'expiry_date' => '2026-11-30'
]);
$request->setUserResolver(function () use ($user) { return $user; });

try {
    $controller = app()->make(App\Http\Controllers\Api\V1\ForwardContractController::class);
    $action = app()->make(App\Domain\Marketplace\Actions\PublishContractAction::class);
    
    // We need to resolve the form request properly to trigger validation
    app()->instance('request', $request);
    $resolvedRequest = app()->make(App\Http\Requests\PublishContractRequest::class);
    
    $controller->store($resolvedRequest, $recommendation, $action);
    echo "Success\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation Failed:\n";
    echo json_encode($e->errors(), JSON_PRETTY_PRINT);
    echo "\n";
} catch (\Exception $e) {
    echo "Other Exception: " . $e->getMessage() . "\n";
}
