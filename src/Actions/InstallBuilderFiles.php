<?php

namespace AliAwwad\FineBuilder\Actions;

use AliAwwad\FineBuilder\Support\Conventions;
use AliAwwad\FineBuilder\Support\Paths;
use Illuminate\Support\Facades\File;

/**
 * Copies the builder's starter files into the site, where they belong to the site
 * (restyle partials, add blocks, edit fieldsets), under the names set in config. Existing
 * files are kept unless forced.
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
                $target = $targetDir.'/'.Conventions::path($stubDir, $file->getRelativePathname());
                $relative = Paths::relative($target);

                if (File::exists($target) && ! $force) {
                    $result['skipped'][] = $relative;

                    continue;
                }

                static::copy($file->getPathname(), $target);
                $result['created'][] = $relative;
            }
        }

        return $result;
    }

    /** Stub directory => where it goes in the site. */
    public static function targets(): array
    {
        return [
            'fieldsets' => Paths::fieldsets(),
            'blueprints/blocks' => Paths::blockBlueprints(),
            'collections' => Paths::collections(),
            'views' => Paths::views(),
            'themeicons' => Paths::icons(),
        ];
    }

    /** Copies a stub, renaming the default block names in text files to the configured ones. */
    public static function copy(string $source, string $target): void
    {
        File::ensureDirectoryExists(dirname($target));

        in_array(pathinfo($source, PATHINFO_EXTENSION), ['yaml', 'html'], true)
            ? File::put($target, Conventions::apply(File::get($source)))
            : File::copy($source, $target);
    }

    public static function stubPath(string $path = ''): string
    {
        return rtrim(__DIR__.'/../../resources/stubs/'.$path, '/');
    }

    public static function isInstalled(): bool
    {
        return File::exists(Paths::fieldsets(config('fine-builder.field', 'fine_builder').'.yaml'));
    }
}
