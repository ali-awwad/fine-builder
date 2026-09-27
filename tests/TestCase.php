<?php

namespace AliAwwad\FineBuilder\Tests;

use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use AliAwwad\FineBuilder\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Stache;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    /** Scratch site for each test: content, blueprints and fieldsets live here. */
    protected string $site;

    protected function setUp(): void
    {
        $this->site = __DIR__.'/__fixtures__/site';

        // Native calls: this runs before parent::setUp() boots the app.
        $this->rimraf($this->site);
        mkdir($this->site.'/collections', 0755, true);
        mkdir($this->site.'/views', 0755, true);

        parent::setUp();

        File::copyDirectory(InstallBuilderFiles::stubPath('fieldsets'), $this->site.'/fieldsets');
        Fieldset::setDirectory($this->site.'/fieldsets');
        Blueprint::setDirectory($this->site.'/blueprints');

        Stache::clear();
    }

    protected function tearDown(): void
    {
        // Icons install to resource_path(), which under Testbench is inside vendor/orchestra.
        $this->rimraf(resource_path('themeicons'));

        parent::tearDown();

        $this->rimraf($this->site);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Sites use the runtime Antlers parser; Testbench would otherwise fall back to the legacy one.
        $app['config']->set('statamic.antlers.version', 'runtime');
        $app['config']->set('view.paths', [__DIR__.'/__fixtures__/site/views']);
        $app['config']->set('statamic.stache.stores.collections.directory', __DIR__.'/__fixtures__/site/collections');
        $app['config']->set('statamic.stache.stores.entries.directory', __DIR__.'/__fixtures__/site/collections');
    }

    protected function fakeLivePreview(bool $live = true): void
    {
        Request::macro('isLivePreview', fn () => $live);
    }

    protected function rimraf(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
