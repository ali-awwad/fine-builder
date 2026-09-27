<?php

namespace AliAwwad\FineBuilder\Actions;

use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Facades\Collection;

class GetCollectionsWithFineBuilder
{
    /** Every collection except Blocks, each flagged with `hasFineBuilder`. */
    public static function execute(): LaravelCollection
    {
        $field = config('fine-builder.field', 'fine_builder');

        return Collection::all()
            ->reject(fn ($collection) => $collection->handle() === 'blocks')
            ->each(function ($collection) use ($field) {
                $collection->hasFineBuilder = $collection->entryBlueprints()
                    ->contains(fn ($blueprint) => $blueprint->hasField($field));
            })
            ->values();
    }
}
