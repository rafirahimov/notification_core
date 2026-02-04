<?php

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;

class KafkaService
{
    private ?Producer $producer = null;

    private function getProducer(): Producer
    {
        if ($this->producer === null) {
            $config = new ProducerConfig();
            $config->setBootstrapServer(config('kafka.brokers'));
            $config->setAcks(-1);
            $config->setConnectTimeout(5);
            $config->setSendTimeout(5);

            $this->producer = new Producer($config);
        }

        return $this->producer;
    }

    public function checkConnection(): array
    {
        try {
            $config = new ProducerConfig();
            $config->setBootstrapServer(config('kafka.brokers'));
            $config->setConnectTimeout(3);

            $producer = new Producer($config);
            $producer->close();

            return [
                'status' => 'connected',
                'brokers' => config('kafka.brokers'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'brokers' => config('kafka.brokers'),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ];
        }
    }

    public function send(string $topic, array $payload, ?string $key = null): bool
    {
        try {
            $producer = $this->getProducer();
            $producer->send($topic, json_encode($payload), $key);
            Log::info("✅ Kafka message sent to topic: {$topic}", ['key' => $key]);
            return true;
        } catch (\Exception $e) {
            Log::error("❌ Kafka send failed: " . $e->getMessage(), [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function listTopics(): array
    {
        return [
            'status' => 'success',
            'brokers' => config('kafka.brokers'),
            'configured_topics' => [
                'push_notifications' => config('kafka.topics.push_notifications'),
                'notification_events' => config('kafka.topics.notification_events'),
                'push_delivery' => config('kafka.topics.push_delivery'),
                'send_mail' => config('kafka.topics.send_mail'),
                'message_expand' => config('kafka.topics.message_expand'),
            ],
            'note' => 'These are configured topics. Use kafka CLI to see all broker topics.',
        ];
    }

    /**
     * Push notification göndər
     */
// KafkaService.php

    public function sendPushNotification(array $payload): array
    {
        $topic = config('kafka.topics.message_expand');
        try {
            $success = $this->send($topic, $payload, $payload['message_id'] ?? null);
            return [
                'success' => $success,
                'error' => null
            ];
        } catch (\Exception $exception) {
            Log::error('Kafka push notification failed', [
                'message' => $exception->getMessage(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        }
    }
    /**
     * Delivery event göndər
     */
    public function sendDeliveryEvent(array $payload): bool
    {
        $topic = config('kafka.topics.push_delivery');
        return $this->send($topic, $payload, $payload['message_id'] ?? null);
    }

    /**
     * User event göndər
     */
    public function sendUserEvent(array $payload): bool
    {
        $topic = config('kafka.topics.notification_events');
        return $this->send($topic, $payload, $payload['event_id'] ?? null);
    }

    /**
     * Mail göndər
     */
    public function sendMail(array $payload): bool
    {
        $topic = config('kafka.topics.send_mail');
        return $this->send($topic, $payload, $payload['mail_id'] ?? 'mail_' . Str::ulid());
    }
}