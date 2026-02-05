<?php
// Modules/Notification/Services/PinService.php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Models\AppUserPin;
use Modules\Notification\Models\Client;

class PinService
{
    use ApiResponse;

    /**
     * List all pins with user counts
     */
    public function list(Client $client): JsonResponse
    {
        try {
            $pins = DB::table('notification.app_user_pin')
                ->where('bundle_id', $client->bundle_id)
                ->select('pin')
                ->selectRaw('COUNT(DISTINCT app_user_id) as user_count')
                ->selectRaw('MIN(id) as id')
                ->selectRaw('MIN(created_at) as created_at')
                ->selectRaw('MAX(updated_at) as updated_at')
                ->groupBy('pin')
                ->orderBy('pin')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'pin' => $item->pin,
                        'user_count' => $item->user_count,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ];
                });

            return $this->buildSuccess($pins);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch pins: ' . $e->getMessage());
        }
    }

    /**
     * Show pin details
     */
    public function show(string $pin, Client $client): JsonResponse
    {
        try {
            // First check if pin exists
            $exists = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->exists();

            if (!$exists) {
                return $this->buildError(404, 'Pin not found');
            }

            // Get pin data
            $pinData = DB::table('notification.app_user_pin')
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->selectRaw('MIN(id) as id')
                ->selectRaw('COUNT(DISTINCT app_user_id) as user_count')
                ->selectRaw('MIN(created_at) as created_at')
                ->selectRaw('MAX(updated_at) as updated_at')
                ->first();

            return $this->buildSuccess([
                'id' => $pinData->id,
                'pin' => $pin,
                'bundle_id' => $client->bundle_id,
                'user_count' => $pinData->user_count,
                'created_at' => $pinData->created_at,
                'updated_at' => $pinData->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch pin: ' . $e->getMessage());
        }
    }

    /**
     * Delete pin (removes all users from this pin)
     */
    public function delete(string $pin, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $deleted = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->delete();

            if ($deleted === 0) {
                DB::rollBack();
                return $this->buildError(404, 'Pin not found');
            }

            DB::commit();

            return $this->buildSuccess(null, 'Pin deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Pin deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Add single user to pin
     */
    public function addUser(string $pin, int $userId, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Check if already exists
            $exists = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->where('app_user_id', $userId)
                ->exists();

            if ($exists) {
                return $this->buildError(400, 'User already added to this pin');
            }

            $record = AppUserPin::query()->create([
                'app_user_id' => $userId,
                'bundle_id' => $client->bundle_id,
                'pin' => $pin,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return $this->buildSuccess([
                'id' => $record->id,
                'pin' => $pin,
                'app_user_id' => $userId,
                'bundle_id' => $client->bundle_id,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ], 'User added to pin successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to add user: ' . $e->getMessage());
        }
    }

    /**
     * Remove single user from pin
     */
    public function removeUser(string $pin, int $userId, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $deleted = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->where('app_user_id', $userId)
                ->delete();

            if ($deleted === 0) {
                DB::rollBack();
                return $this->buildError(404, 'User not found in this pin');
            }

            DB::commit();

            return $this->buildSuccess([
                'pin' => $pin,
                'app_user_id' => $userId,
            ], 'User removed from pin successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to remove user: ' . $e->getMessage());
        }
    }

    /**
     * Get pin users
     */
    public function getUsers(string $pin, Client $client): JsonResponse
    {
        try {
            $exists = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->exists();

            if (!$exists) {
                return $this->buildError(404, 'Pin not found');
            }

            $users = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'app_user_id', 'bundle_id', 'pin', 'created_at', 'updated_at']);

            return $this->buildSuccess([
                'pin' => $pin,
                'bundle_id' => $client->bundle_id,
                'total_users' => $users->count(),
                'users' => $users,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch users: ' . $e->getMessage());
        }
    }
}