<?php

namespace AliAwwad\FineBuilder\Support;

use Illuminate\Support\Str;

/**
 * The names tying a block together, from config: fieldset <prefix><name>, blueprint <name> in the
 * reusable blocks collection, template <views>/<name>. The addon's stubs use the defaults
 * (block_, blocks, sets/blocks); path() and apply() rewrite them to the configured names on copy.
 */
class Conventions
{
    public static function collection(): string
    {
        return config('fine-builder.collection', 'blocks');
    }

    public static function fieldsetPrefix(): string
    {
        return config('fine-builder.fieldset_prefix', 'block_');
    }

    /** Partials folder of the block templates, relative to the views directory. */
    public static function views(): string
    {
        return trim(config('fine-builder.views', 'sets/blocks'), '/');
    }

    public static function fieldset(string $block): string
    {
        return static::fieldsetPrefix().$block;
    }

    public static function view(string $block): string
    {
        return static::views()."/$block";
    }

    public static function collectionTitle(): string
    {
        return Str::of(static::collection())->replace(['_', '-'], ' ')->title()->toString();
    }

    /** A stub file's path inside its stub directory, renamed for the site. */
    public static function path(string $stubDir, string $relative): string
    {
        return match ($stubDir) {
            'fieldsets' => preg_replace('/^block_/', static::fieldsetPrefix(), $relative),
            'collections' => $relative === 'blocks.yaml' ? static::collection().'.yaml' : $relative,
            'views' => preg_replace('#^sets/blocks/#', static::views().'/', $relative),
            default => $relative,
        };
    }

    /** Stub contents with the default names replaced by the configured ones. */
    public static function apply(string $contents): string
    {
        $contents = strtr($contents, [
            'import: block_' => 'import: '.static::fieldsetPrefix(),
            'fields from block_' => 'fields from '.static::fieldsetPrefix(),
            'sets/blocks/' => static::views().'/',
            'the Blocks collection' => 'the '.static::collectionTitle().' collection',
        ]);

        return preg_replace(
            ['/^(\s*collections:\s*\n\s*-\s*)blocks$/m', '/^title: Blocks$/m'],
            ['${1}'.static::collection(), 'title: '.static::collectionTitle()],
            $contents
        );
    }
}
