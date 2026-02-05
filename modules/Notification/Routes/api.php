<?php

use App\Http\Middleware\CheckApiToken;
use Illuminate\Support\Facades\Route;
use Modules\Notification\Controllers\ClientController;
use Modules\Notification\Controllers\DeviceController;
use Modules\Notification\Controllers\KafkaController;
use Modules\Notification\Controllers\PinController;
use Modules\Notification\Controllers\PushController;
use Modules\Notification\Controllers\TagController;

Route::middleware(CheckApiToken::class)
    ->prefix('notification')->group(function () {

        Route::controller(DeviceController::class)
            ->prefix('devices')->group(function () {
                Route::post('register', 'registerDevice');
                Route::post('register-bulk', 'registerDevicesBulk');
                Route::post('deactivate', 'deactivateDevice');
                Route::get('user-devices', 'getUserDevices');
            });

        Route::controller(PushController::class)->prefix('push')->group(function () {
            Route::post('send-to-user', 'sendToUser');
            Route::post('send-to-device', 'sendToDevice');
            Route::post('send-to-group', 'sendToGroup');
            Route::post('send-bulk', 'sendBulk');
        });

        // Client Management
        Route::controller(ClientController::class)->prefix('client')->group(function () {
            Route::get('me', 'me');
            Route::put('update', 'update');
        });

        // Tag Management
        Route::controller(TagController::class)->prefix('tags')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('{tagId}', 'show');
            Route::put('{tagId}', 'update');
            Route::delete('{tagId}', 'destroy');
            Route::post('{tagId}/users', 'addUsers');
            Route::get('{tagId}/users', 'users');
            Route::delete('{tagId}/users', 'removeUsers');
        });

        // Pin Management
        // Pin Management
        Route::controller(PinController::class)->prefix('pins')->group(function () {
            Route::get('/', 'index');
            Route::post('{pin}/users', 'addUsers');
            Route::get('{pin}/users', 'users');
            Route::delete('{pin}/users', 'removeUsers');
            Route::get('{pin}', 'show')->where('pin', '.*');
            Route::delete('{pin}', 'destroy')->where('pin', '.*');
        });
    });

Route::prefix('kafka')->group(function () {
    Route::get('health', [KafkaController::class, 'health']);
    Route::get('topics', [KafkaController::class, 'topics']);
    Route::post('test', [KafkaController::class, 'test']);
});