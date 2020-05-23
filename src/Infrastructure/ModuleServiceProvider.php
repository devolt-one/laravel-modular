<?php

namespace Devolt\Modular\Infrastructure;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * @var string|null Alias for load tranlations and views
     */
    protected ?string $alias = null;

    /**
     * @var string|null Path to module root, used for Caching
     */
    private ?string $path = null;

    private bool $loadMigrations = false;

    private bool $loadTranslations = false;

    /**
     * @var array List of providers to load
     */
    protected array $providers = [];

    /**
     * @var array List of custom Artisan commands
     */
    protected array $commands = [];

    /**
     * @var array List of policies to load
     */
    protected array $policies = [];

    /**
     * @var array List of route bindings
     */
    protected array $routeBindings = [];

    public function register()
    {
        $this->registerProviders();
    }

    /**
     * Boot required registering of views and translations.
     *
     * @throws ReflectionException
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrations();
            $this->loadCommands();
        }

        $this->loadRouteBindings();
        $this->loadPolicies();
        $this->loadTranslations();
    }

    protected function registerProviders()
    {
        collect($this->providers)->each(function ($providerClass) {
            $this->app->register($providerClass);
        });
    }

    /**
     * Register module migrations.
     *
     * @throws ReflectionException
     */
    protected function loadMigrations(): void
    {
        if ($this->loadMigrations) {
            $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        }
    }

    /**
     * Register module custom Artisan commands.
     */
    protected function loadCommands(): void
    {
        if (!empty($this->commands)) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function loadPolicies(): void
    {
        if (!empty($this->policies)) {
            foreach ($this->policies as $key => $value) {
                Gate::policy($key, $value);
            }
        }
    }

    /**
     * Register module translations.
     *
     * @throws ReflectionException
     */
    protected function loadTranslations(): void
    {
        if ($this->loadTranslations) {
            $this->loadTranslationsFrom($this->modulePath('resources/lang'), $this->moduleName());
        }
    }

    /**
     * Register module route bindings.
     */
    protected function loadRouteBindings(): void
    {
        foreach ($this->routeBindings as $key => $binding) {
            Route::model($key, $binding);
        }
    }

    /**
     * Register module factories.
     *
     * @throws ReflectionException
     */
    protected function registerFactories(): void
    {
        $this->callAfterResolving(EloquentFactory::class, function ($factory) {
            $factory->load($this->modulePath('database/factories'));
        });
    }

    /**
     * Detects module name so resources would have their own namespace and/or prefix.
     *
     * @return string
     * @throws ReflectionException
     */
    protected function moduleName(): string
    {
        return $this->alias ?? basename($this->modulePath());
    }

    /**
     * Detects the module base path so resources can be proper loaded on child classes.
     *
     * @param string $append
     * @return string
     * @throws ReflectionException
     */
    protected function modulePath($append = null): string
    {
        if (!$this->path) {
            $reflection = new ReflectionClass($this);

            $basePath = realpath($this->app->basePath($this->app['config']->get('modular.path')));
            $realPath = realpath($reflection->getFileName());

            $this->path = $this->app->basePath($this->app['config']->get('modular.path') . DIRECTORY_SEPARATOR . strtok(substr($realPath, strlen($basePath)), '/'));
        }

        return $append ? ($this->path . DIRECTORY_SEPARATOR . $append) : $this->path;
    }
}
