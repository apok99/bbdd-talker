<?php

use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'BBDD Talker'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'UTC',
    'locale' => 'es',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => ServiceProvider::defaultProviders()
        ->merge(require base_path('bootstrap/providers.php'))
        ->toArray(),
    'aliases' => ServiceProvider::defaultAliases()->merge([
        //
    ])->toArray(),
];
