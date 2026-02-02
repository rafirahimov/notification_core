<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryAttempt extends Model
{

    protected $table = 'notification.delivery_attempt';

    protected $guarded = ['id'];

}