<?php

namespace App\Providers;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\Drivers\FileMakerODataDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SchemaDriver::class, FileMakerODataDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $httpOptions = [
            'verify' => config('my.verify_ssl'),
        ];

        $cfAccessClientId = config('services.cloudflare_access.client_id');
        $cfAccessClientSecret = config('services.cloudflare_access.client_secret');

        if (filled($cfAccessClientId) && filled($cfAccessClientSecret)) {
            $httpOptions['headers'] = [
                'CF-Access-Client-Id' => $cfAccessClientId,
                'CF-Access-Client-Secret' => $cfAccessClientSecret,
            ];
        }

        Http::globalOptions($httpOptions);
    }
}
