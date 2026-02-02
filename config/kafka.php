<?php

return [
    'brokers' => env('KAFKA_BROKERS', '156.67.27.188:27027'),

    'topics' => [
        'push_notifications' => env('KAFKA_TOPIC_PUSH', 'push.send'),
        'push_delivery' => env('KAFKA_TOPIC_PUSH_DELIVERY', 'push.delivery'),
        'notification_events' => env('KAFKA_TOPIC_EVENTS', 'push.user-events'),
        'send_mail' => env('KAFKA_MAIL_SEND', 'send.mail'),
        'message_expand' => env('KAFKA_MESSAGE_EXPAND', 'notification.message.expand'),
    ],

    'consumer_group' => env('KAFKA_CONSUMER_GROUP', 'schedy_notification_group'),

    'producer' => [
        'acks' => -1, // all brokers must acknowledge
        'timeout' => 5000,
        'retries' => 3,
    ],
];