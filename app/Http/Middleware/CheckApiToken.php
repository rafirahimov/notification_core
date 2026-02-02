<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Notification\Models\Client;
use Symfony\Component\HttpFoundation\Response;

// App/Http/Middleware/CheckApiToken.php
class CheckApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('x-api-key');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API key required',
                'error_code' => 'TOKEN_MISSING'
            ], 401);
        }

        $client = Client::query()->where(['token' => $token])->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
                'error_code' => 'TOKEN_INVALID'
            ], 401);
        }

        if(!$client->status){
            return response()->json([
                'success' => false,
                'message' => 'API client is inactive',
                'error_code' => 'CLIENT_INACTIVE'
            ], 403);
        }

        // ✅ Instance property olaraq saxla
        app()->instance('notification.client', $client);

        return $next($request);
    }
}