<?php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Models\Client;
use Modules\Notification\Models\Device;

class DeviceService
{
    use ApiResponse;

    /**
     * Tək device register et
     */
    public function registerDevice(array $data, Client $client): JsonResponse
    {
        try {
            // Köhnə token-ları deaktiv et (başqa device-da eyni token varsa)
            $this->deactivateOldTokens($data['push_token'], $data['device_id'], $client->bundle_id);

            // Device register et
            $device = Device::query()->updateOrCreate(
                [
                    'bundle_id' => $client->bundle_id,
                    'device_id' => $data['device_id'],
                ],
                [
                    'app_user_id' => $data['app_user_id'],
                    'platform' => $data['platform'],
                    'provider' => $this->getProvider($data['platform']),
                    'push_token' => $data['push_token'],
                    'app_version' => $data['app_version'] ?? null,
                    'os_version' => $data['os_version'] ?? null,
                    'model' => $data['model'] ?? null,
                    'language' => $data['locale'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'token_status' => 1,
                    'token_updated_at' => now(),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return $this->buildSuccess([
                'device_id' => $device->device_id,
                'app_user_id' => $device->app_user_id,
                'platform' => $device->platform,
                'token_status' => $device->token_status,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Device registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Toplu device register et
     */
    public function registerDevicesBulk(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $registered = [];
            $failed = [];

            foreach ($data['devices'] as $deviceData) {
                try {
                    // Köhnə token-ları deaktiv et
                    $this->deactivateOldTokens(
                        $deviceData['push_token'],
                        $deviceData['device_id'],
                        $client->bundle_id
                    );

                    // Device register et
                    $device = Device::query()->updateOrCreate(
                        [
                            'bundle_id' => $client->bundle_id,
                            'device_id' => $deviceData['device_id'],
                        ],
                        [
                            'app_user_id' => $deviceData['app_user_id'],
                            'platform' => $deviceData['platform'],
                            'provider' => $this->getProvider($deviceData['platform']),
                            'push_token' => $deviceData['push_token'],
                            'app_version' => $deviceData['app_version'] ?? null,
                            'os_version' => $deviceData['os_version'] ?? null,
                            'model' => $deviceData['model'] ?? null,
                            'language' => $deviceData['locale'] ?? null,
                            'timezone' => $deviceData['timezone'] ?? null,
                            'token_status' => 1,
                            'token_updated_at' => now(),
                            'last_seen_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $registered[] = [
                        'device_id' => $device->device_id,
                        'app_user_id' => $device->app_user_id,
                        'status' => 'success'
                    ];

                } catch (\Exception $e) {
                    $failed[] = [
                        'device_id' => $deviceData['device_id'],
                        'app_user_id' => $deviceData['app_user_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return $this->buildSuccess([
                'total' => count($data['devices']),
                'registered' => count($registered),
                'failed' => count($failed),
                'registered_devices' => $registered,
                'failed_devices' => $failed,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Bulk registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Device-ı deactivate et
     */
    public function deactivateDevice(string $deviceId, Client $client): JsonResponse
    {
        try {
            $updated = Device::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('device_id', $deviceId)
                ->update([
                    'token_status' => 0,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return $this->buildError(404, 'Device not found');
            }

            return $this->buildSuccess(null);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Deactivation failed: ' . $e->getMessage());
        }
    }

    /**
     * User-ın bütün device-larını al
     */
    public function getUserDevices(int $appUserId, Client $client): JsonResponse
    {
        try {
            $devices = Device::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('app_user_id', $appUserId)
                ->orderBy('last_seen_at', 'desc')
                ->get([
                    'id',
                    'device_id',
                    'platform',
                    'provider',
                    'app_version',
                    'os_version',
                    'model',
                    'language',
                    'timezone',
                    'token_status',
                    'last_seen_at',
                    'created_at',
                ]);

            return $this->buildSuccess([
                'app_user_id' => $appUserId,
                'total_devices' => $devices->count(),
                'active_devices' => $devices->where('token_status', 1)->count(),
                'devices' => $devices,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch devices: ' . $e->getMessage());
        }
    }

    /**
     * Köhnə token-ları deaktiv et
     */
    private function deactivateOldTokens(string $pushToken, string $currentDeviceId, string $bundleId): void
    {
        Device::query()
            ->where('bundle_id', $bundleId)
            ->where('push_token', $pushToken)
            ->where('device_id', '!=', $currentDeviceId)
            ->update([
                'token_status' => 0,
                'updated_at' => now(),
            ]);
    }

    /**
     * Platform-a görə provider təyin et
     */
    private function getProvider(string $platform): string
    {
        return  'fcm';
//        return match($platform) {
//            'ios' => 'apns',
//            default => 'fcm'
//        };
    }
}