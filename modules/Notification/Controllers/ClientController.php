<?php
// Modules/Notification/Controllers/ClientController.php

namespace Modules\Notification\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Services\ClientService;

class ClientController
{
    use ApiResponse;

    public function __construct(
        private readonly ClientService $clientService
    ) {}

    /**
     * Get current client info
     */
    public function me(): JsonResponse
    {
        $client = app('notification.client');
        return $this->buildSuccess($client);
    }

    /**
     * Update current client
     */
    public function update(Request $request): JsonResponse
    {
        $client = app('notification.client');
        return $this->clientService->update($request->all(), $client);
    }
}