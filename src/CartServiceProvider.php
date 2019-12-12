<?php

namespace Proxi\ShoppingCart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/cart.php', 'cart'
        );

        $this->app->singleton('cart', function ($app) {
            
            $storage = $app['session'];
            $events = $app['events'];
            $instanceName = 'cart';

            return new Cart($storage, $events, $instanceName, config('cart'));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/cart.php' => config_path('cart.php'),
        ]);
    }
}
