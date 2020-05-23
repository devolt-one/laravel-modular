<?php

namespace Devolt\Modular;

use Illuminate\Support\ServiceProvider;

final class ModularServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__) . '/config/modular.php' => $this->app->configPath('modular.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/modular.php', 'modular'
        );
    }
}
