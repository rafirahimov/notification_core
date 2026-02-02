<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'notification.client';

    protected $guarded = ['id'];

}