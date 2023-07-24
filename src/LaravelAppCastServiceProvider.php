<?php

namespace AriMoralesJordan\LaravelAppCast;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use AriMoralesJordan\LaravelAppCast\Contracts\ClearableRepository;
use AriMoralesJordan\LaravelAppCast\Contracts\EntriesRepository;
use AriMoralesJordan\LaravelAppCast\Contracts\PrunableRepository;
use AriMoralesJordan\LaravelAppCast\Storage\DatabaseEntriesRepository;

class LaravelAppCastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();
        $this->registerPublishing();

        if (!config('telescope.enabled')) {
            return;
        }

        Route::middlewareGroup('telescope', config('telescope.middleware', []));

        $this->registerRoutes();
        $this->registerMigrations();

        LaravelAppCast::start($this->app);
        LaravelAppCast::listenForStorageOpportunities($this->app);

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views', 'telescope'
        );
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ClearCommand::class,
                Console\InstallCommand::class,
                Console\PauseCommand::class,
                Console\PruneCommand::class,
                Console\PublishCommand::class,
                Console\ResumeCommand::class,
            ]);
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'telescope-migrations');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/telescope'),
            ], ['telescope-assets', 'laravel-assets']);

            $this->publishes([
                __DIR__ . '/../config/telescope.php' => config_path('telescope.php'),
            ], 'telescope-config');

            $this->publishes([
                __DIR__ . '/../stubs/TelescopeServiceProvider.stub' => app_path('Providers/TelescopeServiceProvider.php'),
            ], 'telescope-provider');
        }
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        });
    }

    /**
     * Get the Telescope route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'domain' => config('telescope.domain', null),
            'namespace' => 'Laravel\Telescope\Http\Controllers',
            'prefix' => config('telescope.path'),
            'middleware' => 'telescope',
        ];
    }

    /**
     * Register the package's migrations.
     *
     * @return void
     */
    private function registerMigrations()
    {
        if ($this->app->runningInConsole() && $this->shouldMigrate()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Determine if we should register the migrations.
     *
     * @return bool
     */
    protected function shouldMigrate()
    {
        return LaravelAppCast::$runsMigrations && config('telescope.driver') === 'database';
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/telescope.php', 'telescope'
        );

        $this->registerStorageDriver();
    }

    /**
     * Register the package storage driver.
     *
     * @return void
     */
    protected function registerStorageDriver()
    {
        $driver = config('telescope.driver');

        if (method_exists($this, $method = 'register' . ucfirst($driver) . 'Driver')) {
            $this->$method();
        }
    }

    /**
     * Register the package database storage driver.
     *
     * @return void
     */
    protected function registerDatabaseDriver()
    {
        $this->app->singleton(
            EntriesRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            ClearableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            PrunableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give(config('telescope.storage.database.connection'));

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$chunkSize')
            ->give(config('telescope.storage.database.chunk'));
    }
}
