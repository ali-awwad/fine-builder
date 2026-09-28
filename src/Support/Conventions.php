<?php

namespace AliAwwad\FineBuilder\Support;

use Illuminate\Support\Str;

/**
 * The names tying a block together, from config: fieldset <prefix><name>, blueprint <name> in the
 * reusable blocks collection, template <views>/<name>. The addon's stubs use the defaults
 * (fine_builder, block_, blocks, sets/blocks); path() and apply() rewrite them to the configured
 * names on copy.
 */
class Conventions
{
    /** Handle of the builder fieldset, its Replicator field and its sets/<field> partial. */
    public static function field(): string
    {
        return config('fine-builder.field', 'fine_builder');
    }

    public static function template(): string
    {
        return config('fine-builder.template', 'fine_builder');
    }

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
            'fieldsets' => $relative === 'fine_builder.yaml'
                ? static::field().'.yaml'
                : preg_replace('/^block_/', static::fieldsetPrefix(), $relative),
            'collections' => $relative === 'blocks.yaml' ? static::collection().'.yaml' : $relative,
            'views' => match ($relative) {
                'fine_builder.antlers.html' => static::template().'.antlers.html',
                'sets/fine_builder.antlers.html' => 'sets/'.static::field().'.antlers.html',
                default => preg_replace('#^sets/blocks/#', static::views().'/', $relative),
            },
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

        // \b keeps fine_builder_visual (the {{ fbv }} tag's long name) intact.
        return preg_replace(
            ['/^(\s*collections:\s*\n\s*-\s*)blocks$/m', '/^title: Blocks$/m', '/\bfine_builder\b/'],
            ['${1}'.static::collection(), 'title: '.static::collectionTitle(), static::field()],
            $contents
        );
    }
}
