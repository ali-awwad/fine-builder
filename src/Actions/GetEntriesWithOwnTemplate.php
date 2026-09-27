<?php

namespace AliAwwad\FineBuilder\Actions;

use Illuminate\Support\Collection;
use Statamic\Facades\Entry;

/**
 * Entries whose own Template field overrides the collection's template with something other
 * than the builder template. They won't render blocks until that field is changed (or their
 * template includes {{ partial:sets/fine_builder }}).
 */
class GetEntriesWithOwnTemplate
{
    public static function execute(string $collectionHandle): Collection
    {
        $template = config('fine-builder.template', 'fine_builder');

        return Entry::query()
            ->where('collection', $collectionHandle)
            ->get()
            ->filter(fn ($entry) => filled($own = $entry->get('template')) && $own !== $template)
            ->values();
    }
}
