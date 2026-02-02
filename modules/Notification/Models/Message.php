<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'notification.message';
    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'integer',
        'audience_ref' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_SENDING = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = 3;
    const STATUS_CANCELED = 4;
    const STATUS_SCHEDULED = 5;

    // Audience types
    const AUDIENCE_USER = 'user';
    const AUDIENCE_PIN = 'pin';
    const AUDIENCE_DEVICE = 'device';
    const AUDIENCE_TAG = 'tag';
}