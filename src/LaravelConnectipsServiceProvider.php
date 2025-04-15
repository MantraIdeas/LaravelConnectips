<?php

namespace MantraIdeas\LaravelConnectips;


use Illuminate\Support\ServiceProvider;

class LaravelConnectipsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/connectips.php' => config_path('connectips.php'),
        ], 'laravel-connectips-config');
    }
}
