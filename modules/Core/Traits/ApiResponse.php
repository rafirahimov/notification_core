<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

trait ApiResponse
{
    public function success($data = [], $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'code' => $status,
            'response' => request()->method()
        ], $status);
    }

    protected function error($message, $code = 500, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $code);
    }

    private function buildSuccess($data, $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'requestId' => request()->header('X-Request-ID') ?? Str::random(8),
                'timestamp' => now()->toIso8601String(),
                'message' => $message
            ]
        ], $status);
    }

    private function buildError(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => [
                'requestId' => request()->header('X-Request-ID') ?? Str::random(8)
            ]
        ], $status);
    }
}