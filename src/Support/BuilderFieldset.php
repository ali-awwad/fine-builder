<?php

namespace AliAwwad\FineBuilder\Support;

use Statamic\Facades\Fieldset;
use Statamic\Facades\YAML;

/**
 * Reads and edits the set groups of the builder Replicator inside a fieldset array:
 * fields: [ { handle: fine_builder, field: { type: replicator, sets: { <group>: { display, sets: {...} } } } } ]
 */
class BuilderFieldset
{
    public static function handle(): string
    {
        return config('fine-builder.field', 'fine_builder');
    }

    /** The site's builder fieldset contents, or null when it isn't installed. */
    public static function siteContents(): ?array
    {
        return Fieldset::find(static::handle())?->contents();
    }

    /** The builder fieldset shipped with the addon. */
    public static function stubContents(): array
    {
        return YAML::file(__DIR__.'/../../resources/stubs/fieldsets/fine_builder.yaml')->parse();
    }

    /** @return array<string, array{display: ?string, sets: array<string, array>}> */
    public static function groups(array $contents): array
    {
        foreach ($contents['fields'] ?? [] as $field) {
            if (($field['handle'] ?? null) === static::handle()) {
                return $field['field']['sets'] ?? [];
            }
        }

        return [];
    }

    /** @return array<string, array{group: string, groupDisplay: ?string, set: array}> set handle => where it lives */
    public static function sets(array $contents): array
    {
        $sets = [];

        foreach (static::groups($contents) as $group => $groupConfig) {
            foreach ($groupConfig['sets'] ?? [] as $handle => $set) {
                $sets[$handle] = ['group' => $group, 'groupDisplay' => $groupConfig['display'] ?? null, 'set' => $set];
            }
        }

        return $sets;
    }

    /** Adds (or replaces) a set in a group, creating the group before "reusable" when missing. */
    public static function withSet(array $contents, string $group, ?string $groupDisplay, string $handle, array $set): array
    {
        foreach ($contents['fields'] as $i => $field) {
            if (($field['handle'] ?? null) !== static::handle()) {
                continue;
            }

            $groups = $field['field']['sets'] ?? [];

            if (! isset($groups[$group])) {
                $new = [$group => ['display' => $groupDisplay ?? ucfirst($group), 'sets' => []]];
                $position = array_search('reusable', array_keys($groups), true);
                $groups = $position === false
                    ? $groups + $new
                    : array_slice($groups, 0, $position, true) + $new + array_slice($groups, $position, null, true);
            }

            $groups[$group]['sets'][$handle] = $set;
            $contents['fields'][$i]['field']['sets'] = $groups;

            return $contents;
        }

        throw new \RuntimeException('The builder field ['.static::handle().'] was not found in its fieldset.');
    }

    public static function save(array $contents): void
    {
        Fieldset::find(static::handle())->setContents($contents)->save();
    }
}
