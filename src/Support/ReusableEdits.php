<?php

namespace AliAwwad\FineBuilder\Support;

use AliAwwad\FineBuilder\Fieldtypes\FineBuilderBridge;
use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * Text edits made in Live Preview on reusable blocks (entries of the Blocks collection).
 * They wait in the page's bridge field as [block entry id => [path => text]]: Live Preview
 * shows them, and saving the page writes them to the block entries.
 */
class ReusableEdits
{
    /** Handle of the entry's bridge field, if its blueprint has one. */
    public static function handle(EntryContract $entry): ?string
    {
        return $entry->blueprint()?->fields()->all()
            ->first(fn ($field) => $field->type() === FineBuilderBridge::handle())
            ?->handle();
    }

    /** Pending edits of the entry being edited or previewed. */
    public static function of(EntryContract $entry): array
    {
        if (! $handle = static::handle($entry)) {
            return [];
        }

        $edits = $entry->getSupplement($handle) ?? $entry->get($handle);

        return is_array($edits) ? $edits : [];
    }

    /** Live Preview: blocks render with the pending edits, without saving them. */
    public static function preview(array $edits): void
    {
        foreach ($edits as $id => $paths) {
            if ($block = static::block($id)) {
                Entry::substitute(static::apply(clone $block, (array) $paths));
            }
        }
    }

    /** Page save: write the pending edits to each block the current user may edit. */
    public static function save(array $edits): void
    {
        foreach ($edits as $id => $paths) {
            $block = static::block($id);

            if ($block && User::current()?->can('edit', $block)) {
                static::apply($block, (array) $paths)->save();
            }
        }
    }

    /** Sets text values by path. Only text changes: the root field must exist, and arrays (sets, grids) are never replaced. */
    public static function apply(EntryContract $block, array $paths): EntryContract
    {
        $data = $block->data()->all();
        $fields = $block->blueprint()->fields();

        foreach ($paths as $path => $value) {
            $path = (string) $path;
            if (! preg_match('/^\w+(\.\w+)*$/', $path) || ! $fields->has(explode('.', $path)[0])) {
                continue;
            }
            if (is_array(Arr::get($data, $path)) || ! (is_string($value) || $value === null)) {
                continue;
            }
            Arr::set($data, $path, $value ?? '');
        }

        return $block->data($data);
    }

    protected static function block(string $id): ?EntryContract
    {
        $block = Entry::find($id);

        return $block?->collectionHandle() === config('fine-builder.collection', 'blocks') ? $block : null;
    }
}
