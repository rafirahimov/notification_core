<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    protected $table = 'notification.device';

    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'token_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

}