<?php

namespace AliAwwad\FineBuilder;

use AliAwwad\FineBuilder\Fieldtypes\FineBuilderBridge;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Icon;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected $fieldtypes = [
        FineBuilderBridge::class,
    ];

    protected $tags = [
        Tags\FineBuilderVisual::class,
    ];

    protected $commands = [
        Console\Commands\InstallCommand::class,
    ];

    protected $listen = [
        \Statamic\Events\ResponseCreated::class => [Listeners\InjectVisualEditing::class],
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fine-builder.php', 'fine-builder');

        $this->publishes([
            __DIR__.'/../config/fine-builder.php' => config_path('fine-builder.php'),
        ], 'fine-builder-config');

        // Icon fields use `set: themeicons`: the site's copy once installed, the addon's until then.
        Icon::register('themeicons', is_dir(resource_path('themeicons'))
            ? resource_path('themeicons')
            : __DIR__.'/../resources/stubs/themeicons');

        Nav::extend(function ($nav) {
            $nav->create('Fine Builder')
                ->section('Tools')
                ->route('fine-builder.index')
                ->icon('dashboard-layout');
        });
    }
}
