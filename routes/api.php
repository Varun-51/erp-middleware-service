<?php

use App\Http\Controllers\OrderController;
use App\Http\Middleware\ApiKeyMiddleware;

Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
]));

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/customers', [OrderController::class, 'customers']);
    Route::get('/products', [OrderController::class, 'products']);
});
