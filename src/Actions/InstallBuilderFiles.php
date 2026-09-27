<?php

namespace AliAwwad\FineBuilder\Actions;

use AliAwwad\FineBuilder\Support\Paths;
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
                $relative = Paths::relative($target);

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
            'fieldsets' => Paths::fieldsets(),
            'blueprints/blocks' => Paths::blockBlueprints(),
            'collections' => Paths::collections(),
            'views' => Paths::views(),
            'themeicons' => Paths::icons(),
        ];
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
