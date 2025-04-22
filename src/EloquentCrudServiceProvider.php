<?php
namespace SoftlogicGt\EloquentCrud;

use Illuminate\Support\ServiceProvider;

class EloquentCrudServiceProvider extends ServiceProvider
{

    protected $defer = false;

    public function boot()
    {
        $registrar = new \Softlogic\EloquentCrud\ResourceRegistrar($this->app['router']);
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', function () use ($registrar) {
            return $registrar;
        });

        $this->loadViewsFrom(__DIR__ . '/resources/views/', 'eloquentcrud');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang/', 'eloquentcrud');

        $this->publishes([
            __DIR__ . '/resources/lang/' => base_path('/resources/lang/vendor/eloquentcrud'),
        ], 'lang');
    }

    public function register()
    {
        $this->commands([
            Console\MakeEloquentCrudCommand::class,
        ]);
    }
}
