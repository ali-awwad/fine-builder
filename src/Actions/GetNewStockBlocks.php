<?php

namespace AliAwwad\FineBuilder\Actions;

use AliAwwad\FineBuilder\Support\BuilderFieldset;

/**
 * Blocks shipped with the addon that the site's builder doesn't offer yet, e.g. ones added
 * in an addon update. Installing never touches the site's fine_builder fieldset, so these
 * have to be added explicitly (AddStockBlocks).
 */
class GetNewStockBlocks
{
    /** @return array<string, array{display: string, instructions: ?string}> */
    public static function execute(): array
    {
        $site = BuilderFieldset::siteContents();

        if (! $site) {
            return [];
        }

        $existing = BuilderFieldset::sets($site);

        return collect(BuilderFieldset::sets(BuilderFieldset::stubContents()))
            ->diffKeys($existing)
            ->map(fn ($entry, $handle) => [
                'display' => $entry['set']['display'] ?? $handle,
                'instructions' => $entry['set']['instructions'] ?? null,
            ])
            ->all();
    }
}
