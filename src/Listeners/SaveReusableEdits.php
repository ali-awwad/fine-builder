<?php

namespace AliAwwad\FineBuilder\Listeners;

use AliAwwad\FineBuilder\Support\ReusableEdits;
use Statamic\Events\EntrySaving;

/**
 * Saving a page writes the reusable block edits made in its Live Preview to the block
 * entries. The edits themselves are never stored on the page.
 */
class SaveReusableEdits
{
    public function handle(EntrySaving $event): void
    {
        $entry = $event->entry;

        if (! $handle = ReusableEdits::handle($entry)) {
            return;
        }

        $edits = ReusableEdits::of($entry);
        $entry->remove($handle);

        ReusableEdits::save($edits);
    }
}
