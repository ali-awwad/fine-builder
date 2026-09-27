<?php

namespace AliAwwad\FineBuilder\Actions;

use Statamic\Facades\Collection;

/**
 * Removes the builder import and the "Visual editing" helper from a collection's blueprints.
 * Block data stays in the entries, so enabling the builder again brings it back.
 */
class DisableFineBuilderOnCollection
{
    public static function execute(string $collectionHandle): void
    {
        $collection = Collection::findByHandle($collectionHandle);
        $field = config('fine-builder.field', 'fine_builder');

        foreach ($collection->entryBlueprints() as $blueprint) {
            $contents = $blueprint->contents();

            foreach ($contents['tabs'] ?? [] as $tab => $tabContents) {
                foreach ($tabContents['sections'] ?? [] as $s => $section) {
                    $contents['tabs'][$tab]['sections'][$s]['fields'] = collect($section['fields'] ?? [])
                        ->reject(fn ($f) => ($f['import'] ?? null) === $field
                            || ($f['handle'] ?? null) === EnableFineBuilderOnCollection::BRIDGE_HANDLE)
                        ->values()
                        ->all();
                }
            }

            $blueprint->setContents($contents)->save();
        }

        if ($collection->template() === config('fine-builder.template', 'fine_builder')) {
            $collection->template('default')->save();
        }
    }
}
