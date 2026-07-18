<?php
return [
    'name' => env('APP_NAME', 'ReCloset'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'es'),
    'fallback_locale' => 'es',
    'faker_locale' => 'es_ES',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
];
