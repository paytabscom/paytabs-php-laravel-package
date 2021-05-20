<?php

namespace Paytabscom\Laravel_paytabs;

use Illuminate\Support\ServiceProvider;

class PaypageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('paypage', function($app) {
            return new paypage();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/config.php');

        $this->publishes([$path => config_path('paytabs.php')], 'config');
        $this->mergeConfigFrom($path, 'paytabs');
    }
}
