<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Services\KafkaService;

class KafkaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected KafkaService $kafkaService
    ) {}

    /**
     * GET /api/v1/kafka/health
     */
    public function health(): JsonResponse
    {
        $status = $this->kafkaService->checkConnection();

        return $this->success($status);
    }

    /**
     * GET /api/v1/kafka/topics
     */
    public function topics(): JsonResponse
    {
        $topics = $this->kafkaService->listTopics();

        return $this->success($topics);
    }

    /**
     * POST /api/v1/kafka/test
     */
    public function test(): JsonResponse
    {
        $testPayload = [
            'test_id' => 'test_' . uniqid(),
            'message' => 'Kafka connection test',
            'timestamp' => now()->toIso8601String(),
        ];

        $topic = config('kafka.topics.push_notifications');
        $result = $this->kafkaService->send($topic, $testPayload, $testPayload['test_id']);

        if ($result) {
            return $this->success([
                'status' => 'sent',
                'topic' => $topic,
                'payload' => $testPayload,
            ]);
        }

        return $this->error('Failed to send test message', 500);
    }
}