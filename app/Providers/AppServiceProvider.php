<?php

namespace App\Providers;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\Drivers\FileMakerODataDriver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

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
        Http::globalOptions([
            'verify' => Config('my.verify_ssl'),
        ]);
    }
}
