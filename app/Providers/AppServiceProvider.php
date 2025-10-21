<?php

namespace App\Providers;

use App\Services\Chat\DatabaseAwareChatService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseAwareChatService::class, function ($app) {
            return new DatabaseAwareChatService($app['db']->connection());
        });
    }

    public function boot(): void
    {
        //
    }
}
