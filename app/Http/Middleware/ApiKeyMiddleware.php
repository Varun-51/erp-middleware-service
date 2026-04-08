<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedApiKey = env('API_KEY');

        if (empty($expectedApiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key not configured',
            ], 500);
        }

        if ($apiKey !== $expectedApiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key',
            ], 401);
        }

        return $next($request);
    }
}
