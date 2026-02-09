<?php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Models\AppUserPin;
use Modules\Notification\Models\AppUserTag;
use Modules\Notification\Models\Device;
use Modules\Notification\Models\Message;
use Modules\Notification\Models\Tag;
use Modules\Notification\Models\Client;

class PushNotificationService
{
    use ApiResponse;

    public function __construct(
        private readonly KafkaService $kafkaService
    ) {}

    /**
     * Send push to user (by user_id or pin)
     */
    public function sendToUser(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            // user_id və ya pin-ə görə user tap
            if (isset($data['user_id'])) {
                $userId = $data['user_id'];
            } else {
                // pin-ə görə user_id tap
                $pin = AppUserPin::query()
                    ->where('bundle_id', $client->bundle_id)
                    ->where('pin', $data['pin'])
                    ->first();

                if (!$pin) {
                    return $this->buildError(404, 'PIN not found');
                }

                $userId = $pin->app_user_id;
            }

            // Message yarat
            $message = Message::query()->create([
                'bundle_id' => $client->bundle_id,
                'category' => $data['meta']['channel'] ?? 'system',
                'title' => strip_tags($data['message']['title']),
                'body' => strip_tags($data['message']['body']),
                'action_url' => $data['meta']['route'] ?? null,
                'image_url' => null,
                'audience_type' => Message::AUDIENCE_USER,
                'audience_ref' => $userId,
                'status' => Message::STATUS_SCHEDULED,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kafka-ya göndər
            $sent = $this->kafkaService->sendPushNotification([
                'bundle_id' => $client->bundle_id,
                'message_id' => $message->id
            ]);

            if (!$sent) {
                DB::rollBack();
                return $this->buildError(500, 'Failed to queue message', [$result['error'] ?? 'Unknown error']);
            }

            DB::commit();

            return $this->buildSuccess([
                'push_id' => 'push_' . str_pad($message->id, 10, '0', STR_PAD_LEFT)
            ], 'Push notification queued', 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Push send failed: ' . $e->getMessage());
        }
    }

    /**
     * Send push to specific device
     */
    public function sendToDevice(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            // device_id-yə görə user_id tap
            $device = Device::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('device_id', $data['device_id'])
                ->first();

            if (!$device || !$device->app_user_id) {
                return $this->buildError(404, 'Device not found or not linked to user');
            }

            // Message yarat
            $message = Message::create([
                'bundle_id' => $client->bundle_id,
                'category' => $data['meta']['channel'] ?? 'system',
                'title' => strip_tags($data['message']['title']),
                'body' => strip_tags($data['message']['body']),
                'action_url' => $data['meta']['route'] ?? null,
                'image_url' => null,
                'audience_type' => Message::AUDIENCE_DEVICE,
                'audience_ref' => $device->app_user_id,
                'status' => Message::STATUS_SCHEDULED,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kafka-ya göndər
            $sent = $this->kafkaService->sendPushNotification([
                'bundle_id' => $client->bundle_id,
                'message_id' => $message->id
            ]);

            if (!$sent) {
                DB::rollBack();
                return $this->buildError(500, 'Failed to queue message');
            }

            DB::commit();

            return $this->buildSuccess([
                'push_id' => 'push_' . str_pad($message->id, 10, '0', STR_PAD_LEFT)
            ], 'Push notification queued', 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Push send failed: ' . $e->getMessage());
        }
    }

    /**
     * Send push to group (tag)
     */
    public function sendToGroup(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Tag name-ə görə tag_id tap
            $tag = Tag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('name', $data['tag'])
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            // Bu tag-a aid user sayını yoxla
            $userCount = AppUserTag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('tag_id', $tag->id)
                ->distinct('app_user_id')
                ->count('app_user_id');

            if ($userCount === 0) {
                return $this->buildError(404, 'No users found with this tag');
            }

            // Message yarat (audience_ref = tag_id)
            $message = Message::create([
                'bundle_id' => $client->bundle_id,
                'category' => $data['meta']['channel'] ?? 'system',
                'title' => strip_tags($data['message']['title']),
                'body' => strip_tags($data['message']['body']),
                'action_url' => $data['meta']['route'] ?? null,
                'image_url' => null,
                'audience_type' => Message::AUDIENCE_TAG,
                'audience_ref' => $tag->id, // ✅ tag_id
                'status' => Message::STATUS_SCHEDULED,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kafka-ya göndər
            $sent = $this->kafkaService->sendPushNotification([
                'bundle_id' => $client->bundle_id,
                'message_id' => $message->id
            ]);

            if (!$sent) {
                DB::rollBack();
                return $this->buildError(500, 'Failed to queue message');
            }

            DB::commit();

            return $this->buildSuccess([
                'push_id' => 'push_' . str_pad($message->id, 10, '0', STR_PAD_LEFT),
                'target_users' => $userCount
            ], 'Push notification queued', 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Push send failed: ' . $e->getMessage());
        }
    }

    /**
     * Send push to multiple targets (bulk)
     */
    public function sendBulk(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userIds = [];
            $failed = [];

            // Hər target-i resolve et
            foreach ($data['targets'] as $target) {
                try {
                    $userId = $this->resolveTarget($target, $client->bundle_id);
                    if ($userId) {
                        $userIds[] = $userId;
                    } else {
                        $failed[] = [
                            'type' => $target['type'],
                            'value' => $target['value'],
                            'reason' => 'Not found'
                        ];
                    }
                } catch (\Exception $e) {
                    $failed[] = [
                        'type' => $target['type'],
                        'value' => $target['value'],
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Unique user_id-lər
            $userIds = array_unique($userIds);

            if (empty($userIds)) {
                DB::rollBack();
                return $this->buildError(400, 'No valid targets found', [
                    'failed_targets' => $failed
                ]);
            }

            $messageIds = [];

            // Hər user üçün ayrı message yarat
            foreach ($userIds as $userId) {
                $message = Message::query()->create([
                    'bundle_id' => $client->bundle_id,
                    'category' => $data['meta']['channel'] ?? 'system',
                    'title' => strip_tags($data['message']['title']),
                    'body' => strip_tags($data['message']['body']),
                    'action_url' => $data['meta']['route'] ?? null,
                    'image_url' => null,
                    'audience_type' => Message::AUDIENCE_USER,
                    'audience_ref' => $userId,
                    'status' => Message::STATUS_SCHEDULED,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Kafka-ya göndər
                $sent = $this->kafkaService->sendPushNotification([
                    'bundle_id' => $client->bundle_id,
                    'message_id' => $message->id
                ]);

                if ($sent) {
                    $messageIds[] = $message->id;
                }
            }

            DB::commit();

            return $this->buildSuccess([
                'total_targets' => count($data['targets']),
                'successful' => count($messageIds),
                'failed' => count($failed),
                'push_ids' => array_map(fn($id) => 'push_' . str_pad($id, 10, '0', STR_PAD_LEFT), $messageIds),
                'failed_targets' => $failed
            ], 'Bulk push notifications queued', 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Bulk push send failed: ' . $e->getMessage());
        }
    }

    /**
     * Target-i user_id-yə resolve et
     */
    private function resolveTarget(array $target, string $bundleId): ?int
    {
        return match($target['type']) {
            'user_id' => (int) $target['value'],

            'pin' => AppUserPin::query()
                ->where('bundle_id', $bundleId)
                ->where('pin', $target['value'])
                ->value('app_user_id'),

            'device_id' => Device::query()
                ->where('bundle_id', $bundleId)
                ->where('device_id', $target['value'])
                ->value('app_user_id'),

            default => null
        };
    }
}