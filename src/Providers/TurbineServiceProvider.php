<?php

namespace PowerComponents\Turbine\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PowerComponents\Turbine\DataSource\DataSourceManager;
use PowerComponents\Turbine\DataSource\Processors\Database\Handlers\{SearchHandler, SearchHandlerContract};
use PowerComponents\Turbine\Support\Actions\ButtonMacros;
use PowerComponents\Turbine\Support\TableCache;

class TurbineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/turbine.php', 'turbine');

        $this->app->singleton(DataSourceManager::class, fn () => new DataSourceManager());

        $this->app->bind(
            SearchHandlerContract::class,
            fn ($app, array $params) => new SearchHandler($params['component'])
        );
    }

    public function boot(): void
    {
        ButtonMacros::register();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/turbine.php' => config_path('turbine.php'),
            ], 'turbine-config');
        }

        Event::listen(MigrationsEnded::class, fn () => TableCache::forgetAll());
    }
}
