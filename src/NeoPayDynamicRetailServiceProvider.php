<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

use Illuminate\Support\ServiceProvider;

class NeoPayDynamicRetailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/neopay-dynamic-retail.php', 'neopay-dynamic-retail');

        $this->app->singleton(PosClient::class);
        $this->app->singleton(Pos::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/neopay-dynamic-retail.php' => config_path('neopay-dynamic-retail.php'),
        ], 'neopay-dynamic-retail-config');
    }
}
