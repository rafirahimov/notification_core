<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Requests\RegisterDeviceRequest;
use Modules\Notification\Requests\RegisterDevicesBulkRequest;
use Modules\Notification\Services\DeviceService;

class DeviceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DeviceService $deviceService
    ) {}

    /**
     * Tək device register et
     */
    public function registerDevice(RegisterDeviceRequest $request): JsonResponse
    {
        $client = app('notification.client');

        return $this->deviceService->registerDevice($request->validated(), $client);
    }

    /**
     * Toplu device register et
     */
    public function registerDevicesBulk(RegisterDevicesBulkRequest $request): JsonResponse
    {
        $client = app('notification.client');

        return $this->deviceService->registerDevicesBulk($request->validated(), $client);
    }

    /**
     * Device-ı deactivate et
     */
    public function deactivateDevice(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $client = app('notification.client');

        return $this->deviceService->deactivateDevice($request->device_id, $client);
    }

    /**
     * User-ın bütün device-larını al
     */
    public function getUserDevices(Request $request): JsonResponse
    {
        $request->validate([
            'app_user_id' => 'required|integer',
        ]);

        $client = app('notification.client');

        return $this->deviceService->getUserDevices($request->app_user_id, $client);
    }
}