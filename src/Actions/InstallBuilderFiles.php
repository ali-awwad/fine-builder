<?php

namespace AliAwwad\FineBuilder\Actions;

use Illuminate\Support\Facades\File;

/**
 * Copies the builder's starter files into the site, where they belong to the site
 * (restyle partials, add blocks, edit fieldsets). Existing files are kept unless forced.
 */
class InstallBuilderFiles
{
    /**
     * @param  array<string, string>|null  $targets  stub dir => site dir, defaults to targets()
     * @return array{created: list<string>, skipped: list<string>}
     */
    public static function execute(bool $force = false, ?array $targets = null): array
    {
        $result = ['created' => [], 'skipped' => []];

        foreach ($targets ?? static::targets() as $stubDir => $targetDir) {
            foreach (File::allFiles(static::stubPath($stubDir)) as $file) {
                $target = $targetDir.'/'.$file->getRelativePathname();
                $relative = ltrim(str_replace(base_path(), '', $target), '/');

                if (File::exists($target) && ! $force) {
                    $result['skipped'][] = $relative;

                    continue;
                }

                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
                $result['created'][] = $relative;
            }
        }

        return $result;
    }

    /** Stub directory => where it goes in the site. */
    public static function targets(): array
    {
        return [
            'fieldsets' => resource_path('fieldsets'),
            'blueprints/blocks' => resource_path('blueprints/collections/blocks'),
            'collections' => config('statamic.stache.stores.collections.directory', base_path('content/collections')),
            'views' => resource_path('views'),
            'themeicons' => resource_path('themeicons'),
        ];
    }

    public static function stubPath(string $path = ''): string
    {
        return rtrim(__DIR__.'/../../resources/stubs/'.$path, '/');
    }

    public static function isInstalled(): bool
    {
        return File::exists(resource_path('fieldsets/'.config('fine-builder.field', 'fine_builder').'.yaml'));
    }
}
