<?php

namespace AliAwwad\FineBuilder\Actions;

use AliAwwad\FineBuilder\Support\BuilderFieldset;
use AliAwwad\FineBuilder\Support\Paths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Scaffolds a new block in the site, following the builder's one-name convention:
 * fieldset block_<handle>, set <handle>, Blocks blueprint <handle>, template sets/blocks/<handle>.
 */
class MakeBlock
{
    /** Handles that would collide with the Replicator's own keys or the reusable "block" set. */
    public const RESERVED = ['block', 'type', 'id', 'enabled'];

    /** @return list<string> created paths, relative to the project */
    public static function execute(string $handle, ?string $display = null, string $group = 'blocks', bool $reusable = true): array
    {
        static::validate($handle);

        $contents = BuilderFieldset::siteContents()
            ?? throw new InvalidArgumentException('Install the builder files first (php please fine-builder:install).');

        if (array_key_exists($handle, BuilderFieldset::sets($contents))) {
            throw new InvalidArgumentException("The builder already has a [$handle] block.");
        }

        $display ??= Str::of($handle)->replace('_', ' ')->title()->toString();

        $files = [
            'fieldset.yaml.stub' => Paths::fieldsets("block_$handle.yaml"),
            'view.antlers.html.stub' => Paths::views("sets/blocks/$handle.antlers.html"),
        ];

        if ($reusable) {
            $files['blueprint.yaml.stub'] = Paths::blockBlueprints("$handle.yaml");
        }

        foreach ($files as $target) {
            if (File::exists($target)) {
                throw new InvalidArgumentException('File already exists: '.Paths::relative($target));
            }
        }

        foreach ($files as $stub => $target) {
            // YAML stubs quote the name in single quotes, which escape as ''.
            $name = str_ends_with($stub, '.yaml.stub') ? str_replace("'", "''", $display) : $display;
            $content = str_replace(['DummyHandle', 'DummyDisplay'], [$handle, $name], File::get(__DIR__.'/../../resources/scaffold/'.$stub));

            File::ensureDirectoryExists(dirname($target));
            File::put($target, $content);
        }

        BuilderFieldset::save(BuilderFieldset::withSet($contents, $group, null, $handle, [
            'display' => $display,
            'instructions' => 'Heading, text and buttons.',
            'fields' => [['import' => "block_$handle"]],
        ]));

        return array_map(fn ($path) => Paths::relative($path), array_values($files));
    }

    public static function validate(string $handle): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $handle)) {
            throw new InvalidArgumentException("[$handle] is not a valid block handle: use snake_case, e.g. testimonials or logo_cloud.");
        }

        if (in_array($handle, self::RESERVED, true)) {
            throw new InvalidArgumentException("[$handle] is reserved.");
        }
    }
}
