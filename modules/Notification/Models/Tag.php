<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{

    protected $table = 'notification.tag';

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}