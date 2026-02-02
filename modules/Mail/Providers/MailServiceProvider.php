<?php

namespace Modules\Mail\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        Route::prefix('api/core')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}