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
        $this->mergeConfigFrom(
            __DIR__ . '/config/config.php', 'paytabs'
        );

        $this->app->make(\Paytabscom\Laravel_paytabs\Controllers\PaytabsLaravelListenerApi::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('paytabs.php'),
        ],'paytabs');

        include __DIR__.'/../routes/routes.php';
    }
}
