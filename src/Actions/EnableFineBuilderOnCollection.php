<?php

namespace AliAwwad\FineBuilder\Actions;

use Statamic\Facades\Collection;

/**
 * Adds the builder to every entry blueprint of a collection (next to the title), puts the
 * "Visual editing" helper in the sidebar, updates Live Preview in place instead of reloading,
 * and switches the collection to the builder template if it still uses `default`.
 */
class EnableFineBuilderOnCollection
{
    public const BRIDGE_HANDLE = 'visual_editing';

    public static function execute(string $collectionHandle): void
    {
        $collection = Collection::findByHandle($collectionHandle);
        $field = config('fine-builder.field', 'fine_builder');

        foreach ($collection->entryBlueprints() as $blueprint) {
            $contents = $blueprint->contents();

            if (! $blueprint->hasField($field)) {
                $contents = static::insertAfterTitle($contents, ['import' => $field]);
            }

            if (! $blueprint->hasField(self::BRIDGE_HANDLE)) {
                $contents['tabs']['sidebar'] ??= ['display' => __('Sidebar'), 'sections' => [['fields' => []]]];
                $contents['tabs']['sidebar']['sections'][0]['fields'] ??= [];
                array_unshift($contents['tabs']['sidebar']['sections'][0]['fields'], [
                    'handle' => self::BRIDGE_HANDLE,
                    'field' => [
                        'type' => 'fine_builder_bridge',
                        'display' => __('Visual editing'),
                        'field' => $field,
                        'listable' => false,
                    ],
                ]);
            }

            $blueprint->setContents($contents)->save();
        }

        $collection->previewTargets(
            $collection->basePreviewTargets()->map(fn ($target) => ['refresh' => false] + $target)->all()
        );

        if (in_array($collection->template(), [null, 'default'], true)) {
            $collection->template(config('fine-builder.template', 'fine_builder'));
        }

        $collection->save();
    }

    protected static function insertAfterTitle(array $contents, array $item): array
    {
        foreach ($contents['tabs'] ?? [] as $tab => $tabContents) {
            foreach ($tabContents['sections'] ?? [] as $s => $section) {
                foreach ($section['fields'] ?? [] as $i => $f) {
                    if (($f['handle'] ?? null) === 'title') {
                        array_splice($contents['tabs'][$tab]['sections'][$s]['fields'], $i + 1, 0, [$item]);

                        return $contents;
                    }
                }
            }
        }

        $tab = array_key_first($contents['tabs'] ?? []) ?? 'main';
        $contents['tabs'][$tab]['sections'][0]['fields'][] = $item;

        return $contents;
    }
}
