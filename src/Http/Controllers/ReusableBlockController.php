<?php

namespace AliAwwad\FineBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\Facades\Entry;

/**
 * Saves a text edit made in Live Preview on a reusable block (an entry of the Blocks
 * collection). The page's form doesn't hold that data, so the entry is saved directly.
 */
class ReusableBlockController
{
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'regex:/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*$/'],
            'value' => ['nullable', 'string'],
        ]);

        $entry = Entry::find($id);

        abort_unless($entry && $entry->collectionHandle() === config('fine-builder.collection', 'blocks'), 404);
        abort_unless($request->user()->can('edit', $entry), 403);

        // Only text can change: the root field must exist, and arrays (sets, grids) are never replaced.
        $values = $entry->data()->all();
        $current = Arr::get($values, $data['path'], $missing = new \stdClass);
        abort_if(! $entry->blueprint()->hasField(explode('.', $data['path'])[0]), 422);
        abort_if(is_array($current) || is_object($current) && $current !== $missing, 422);

        Arr::set($values, $data['path'], $data['value'] ?? '');
        $entry->data($values)->save();

        return ['saved' => true];
    }
}
