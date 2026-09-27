<?php

namespace AliAwwad\FineBuilder\Support;

use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;

/** Where builder files live in the site, following Statamic's and Laravel's configured directories. */
class Paths
{
    public static function fieldsets(string $file = ''): string
    {
        return static::join(Fieldset::directory(), $file);
    }

    public static function blockBlueprints(string $file = ''): string
    {
        return static::join(Blueprint::directory().'/collections/blocks', $file);
    }

    public static function views(string $file = ''): string
    {
        return static::join(config('view.paths.0', resource_path('views')), $file);
    }

    public static function collections(string $file = ''): string
    {
        return static::join(config('statamic.stache.stores.collections.directory', base_path('content/collections')), $file);
    }

    public static function icons(string $file = ''): string
    {
        return static::join(resource_path('themeicons'), $file);
    }

    public static function relative(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), '/');
    }

    protected static function join(string $dir, string $file): string
    {
        return rtrim($dir, '/').($file === '' ? '' : '/'.ltrim($file, '/'));
    }
}
