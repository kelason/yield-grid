<?php

use App\Shared\Controllers\ApiDocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/docs', [ApiDocsController::class, 'ui']);
    Route::get('/docs/openapi.json', [ApiDocsController::class, 'spec']);
});
