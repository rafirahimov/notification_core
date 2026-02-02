<?php

use Illuminate\Support\Facades\Route;
use Modules\Mail\Controllers\MailController;
use Modules\Notification\Controllers\DeviceController;
use Modules\Notification\Controllers\KafkaController;
use Modules\Notification\Controllers\PushController;

Route::middleware('jwt.auth')->group(function () {

    Route::prefix('mail')->group(function () {

        Route::post('send-all-users', [MailController::class, 'sendAllUsers']);
        Route::post('send-user/{userId}', [MailController::class, 'sendUser']);
    });
});
