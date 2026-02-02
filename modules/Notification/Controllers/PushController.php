<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Requests\SendPushBulkRequest;
use Modules\Notification\Requests\SendPushToDeviceRequest;
use Modules\Notification\Requests\SendPushToGroupRequest;
use Modules\Notification\Requests\SendPushToUserRequest;
use Modules\Notification\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushController extends Controller
{

    public function __construct(
        private readonly PushNotificationService $pushNotificationService
    ) {}

    /**
     * Send push to user (by user_id or pin)
     */
    public function sendToUser(SendPushToUserRequest $request): JsonResponse
    {
        $client = app('notification.client');
        return $this->pushNotificationService->sendToUser($request->validated(), $client);
    }

    /**
     * Send push to specific device
     */
    public function sendToDevice(SendPushToDeviceRequest $request): JsonResponse
    {
        $client = app('notification.client');
        return $this->pushNotificationService->sendToDevice($request->validated(), $client);
    }

    /**
     * Send push to group (tag)
     */
    public function sendToGroup(SendPushToGroupRequest $request): JsonResponse
    {
        $client = app('notification.client');
        return $this->pushNotificationService->sendToGroup($request->validated(), $client);
    }

    /**
     * Send push to multiple targets (bulk)
     */
    public function sendBulk(SendPushBulkRequest $request): JsonResponse
    {
        $client = app('notification.client');
        return $this->pushNotificationService->sendBulk($request->validated(), $client);
    }

}