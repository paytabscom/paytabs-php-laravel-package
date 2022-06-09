<?php

namespace Paytabscom\LaravelPaytabs;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class PaypageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (! app()->configurationIsCached()) {
            $this->mergeConfigFrom(
                __DIR__ . '/config/config.php', 'paytabs'
            );
        }
        
        $this->app->bind('Paypage', function($app) {
            return new Paypage();
        });

        
        $this->app->make(\Paytabscom\LaravelPaytabs\Controllers\PaytabsLaravelListenerApi::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/config.php' => config_path('paytabs.php'),
            ],'paytabs');
        }

        $this->defineRoutes();

        $this->app->make('config')->set('logging.channels.paytabs', [
            'driver' => 'single',
            'path' => config('paytabs.log_file'),
            'level' => 'debug',
        ]);
    }

    /**
     * Define the Paytabs routes.
     *
     * @return void
     */
    protected function defineRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        // Since we are going to define only one route we can skip this and simply add it 
        // $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');

        Route::post('/paymentIPN', [\Paytabscom\LaravelPaytabs\Controllers\PaytabsLaravelListenerApi::class, 'paymentIPN'])->name('payment_ipn');
        
    }

}
