<?php

namespace AliAwwad\FineBuilder\Actions;

use AliAwwad\FineBuilder\Support\BuilderFieldset;
use AliAwwad\FineBuilder\Support\Paths;
use Illuminate\Support\Facades\File;

/**
 * Adds addon-shipped blocks to the site's builder: copies their missing files
 * (fieldset, Blocks blueprint, template) and adds their sets to the same group.
 */
class AddStockBlocks
{
    /** @param  list<string>|null  $handles  defaults to every new stock block */
    public static function execute(?array $handles = null): array
    {
        $new = GetNewStockBlocks::execute();
        $handles = array_values(array_intersect($handles ?? array_keys($new), array_keys($new)));

        if (! $handles) {
            return [];
        }

        $stubSets = BuilderFieldset::sets(BuilderFieldset::stubContents());
        $contents = BuilderFieldset::siteContents();

        foreach ($handles as $handle) {
            $files = [
                "fieldsets/block_$handle.yaml" => Paths::fieldsets("block_$handle.yaml"),
                "blueprints/blocks/$handle.yaml" => Paths::blockBlueprints("$handle.yaml"),
                "views/sets/blocks/$handle.antlers.html" => Paths::views("sets/blocks/$handle.antlers.html"),
            ];

            foreach ($files as $stub => $target) {
                if (File::exists($source = InstallBuilderFiles::stubPath($stub)) && ! File::exists($target)) {
                    File::ensureDirectoryExists(dirname($target));
                    File::copy($source, $target);
                }
            }

            $stub = $stubSets[$handle];
            $contents = BuilderFieldset::withSet($contents, $stub['group'], $stub['groupDisplay'], $handle, $stub['set']);
        }

        BuilderFieldset::save($contents);

        return $handles;
    }
}
