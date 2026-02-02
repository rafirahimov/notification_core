<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class MessageRecipient extends Model
{

    protected $table = 'notification.message_recipient';

    protected $guarded = ['id'];

}