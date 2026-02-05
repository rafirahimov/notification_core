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
            $pins = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->selectRaw('COUNT(DISTINCT app_user_id) as user_count')
                ->selectRaw('MIN(created_at) as created_at')
                ->groupBy('pin')
                ->orderBy('pin')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'app_user_id' => $item->app_user_id,
                        'bundle_id' => $item->bundle_id,
                        'pin' => $item->pin,
                        'user_count' => $item->user_count,
                        'created_at' => $item->created_at,
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
            $pinData = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->first();

            if (!$pinData) {
                return $this->buildError(404, 'Pin not found');
            }

            $userCount = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->distinct('app_user_id')
                ->count('app_user_id');

            return $this->buildSuccess([
                'id' => $pinData->id,
                'app_user_id' => $pinData->app_user_id,
                'bundle_id' => $pinData->bundle_id,
                'pin' => $pin,
                'user_count' => $userCount,
                'created_at' => $pinData->created_at,
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
     * Add users to pin
     */
    public function addUsers(string $pin, array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $addedCount = 0;
            $skippedCount = 0;

            foreach ($data['user_ids'] as $userId) {
                // Check if already exists
                $exists = AppUserPin::query()
                    ->where('bundle_id', $client->bundle_id)
                    ->where('pin', $pin)
                    ->where('app_user_id', $userId)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                AppUserPin::query()->create([
                    'app_user_id' => $userId,
                    'bundle_id' => $client->bundle_id,
                    'pin' => $pin,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $addedCount++;
            }

            DB::commit();

            return $this->buildSuccess([
                'pin' => $pin,
                'added' => $addedCount,
                'skipped' => $skippedCount,
                'total' => count($data['user_ids']),
            ], 'Users added to pin');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to add users: ' . $e->getMessage());
        }
    }

    /**
     * Remove users from pin
     */
    public function removeUsers(string $pin, array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $deleted = AppUserPin::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('pin', $pin)
                ->whereIn('app_user_id', $data['user_ids'])
                ->delete();

            DB::commit();

            return $this->buildSuccess([
                'pin' => $pin,
                'removed' => $deleted,
                'requested' => count($data['user_ids']),
            ], 'Users removed from pin');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to remove users: ' . $e->getMessage());
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
                ->get(['app_user_id', 'created_at']);

            return $this->buildSuccess([
                'pin' => $pin,
                'total_users' => $users->count(),
                'users' => $users,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch users: ' . $e->getMessage());
        }
    }
}