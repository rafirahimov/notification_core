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
     * Add single user to pin
     */
    public function addUser(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|max:255',
            'user_id' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->pinService->addUser($request->all(), $client);
    }

    /**
     * Remove single user from pin
     */
    public function removeUser(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|max:255',
            'user_id' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->pinService->removeUser($request->all(), $client);
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