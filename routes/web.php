<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'ERP Integration Service',
        'version' => '1.0.0',
        'api_docs' => 'See /api/health for health check',
    ]);
});
