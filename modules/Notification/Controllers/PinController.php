<?php
// Modules/Notification/Controllers/PinController.php

namespace Modules\Notification\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Services\PinService;

class PinController
{
    use ApiResponse;

    public function __construct(
        private readonly PinService $pinService
    ) {}

    /**
     * List all pins
     */
    public function index(): JsonResponse
    {
        $client = app('notification.client');
        return $this->pinService->list($client);
    }

    /**
     * Get pin details
     */
    public function show(string $pin): JsonResponse
    {
        $client = app('notification.client');
        return $this->pinService->show($pin, $client);
    }

    /**
     * Delete pin
     */
    public function destroy(string $pin): JsonResponse
    {
        $client = app('notification.client');
        return $this->pinService->delete($pin, $client);
    }

    /**
     * Add users to pin
     */
    public function addUsers(Request $request, string $pin): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1|max:1000',
            'user_ids.*' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->pinService->addUsers($pin, $request->all(), $client);
    }

    /**
     * Remove users from pin
     */
    public function removeUsers(Request $request, string $pin): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->pinService->removeUsers($pin, $request->all(), $client);
    }

    /**
     * Get pin users
     */
    public function users(string $pin): JsonResponse
    {
        $client = app('notification.client');
        return $this->pinService->getUsers($pin, $client);
    }
}