<?php

namespace AliAwwad\FineBuilder\Tests\Actions;

use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use AliAwwad\FineBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class InstallBuilderFilesTest extends TestCase
{
    private function targets(): array
    {
        return collect(InstallBuilderFiles::targets())
            ->map(fn ($dir, $stub) => $this->site.'/install/'.$stub)
            ->all();
    }

    #[Test]
    public function it_copies_every_stub_into_the_site(): void
    {
        $result = InstallBuilderFiles::execute(targets: $this->targets());

        $this->assertEmpty($result['skipped']);
        $this->assertFileExists($this->site.'/install/fieldsets/fine_builder.yaml');
        $this->assertFileExists($this->site.'/install/views/sets/blocks/hero.antlers.html');
        $this->assertFileExists($this->site.'/install/views/fine_builder.antlers.html');
        $this->assertFileExists($this->site.'/install/blueprints/blocks/hero.yaml');
        $this->assertFileExists($this->site.'/install/collections/blocks.yaml');
        $this->assertFileExists($this->site.'/install/themeicons/check.svg');
    }

    #[Test]
    public function it_installs_under_the_configured_block_names(): void
    {
        config(['fine-builder.collection' => 'sections', 'fine-builder.fieldset_prefix' => 'section_', 'fine-builder.views' => 'partials/sections']);

        InstallBuilderFiles::execute(targets: $this->targets());
        $install = $this->site.'/install';

        $this->assertFileExists("$install/fieldsets/section_hero.yaml");
        $this->assertFileDoesNotExist("$install/fieldsets/block_hero.yaml");
        $this->assertFileExists("$install/fieldsets/common.yaml");
        $this->assertFileExists("$install/views/partials/sections/hero.antlers.html");
        $this->assertDirectoryDoesNotExist("$install/views/sets/blocks");
        $this->assertFileExists("$install/views/sets/fine_builder.antlers.html");
        $this->assertSame('Sections', \Statamic\Facades\YAML::file("$install/collections/sections.yaml")->parse()['title']);

        $builder = \Statamic\Facades\YAML::file("$install/fieldsets/fine_builder.yaml")->parse()['fields'][0]['field'];
        $this->assertSame([['import' => 'section_hero']], $builder['sets']['blocks']['sets']['hero']['fields']);
        $this->assertSame(['sections'], $builder['sets']['reusable']['sets']['block']['fields'][0]['field']['collections']);
        $this->assertStringContainsString('the Sections collection', $builder['instructions']);

        $this->assertStringContainsString('import: section_hero', File::get("$install/blueprints/blocks/hero.yaml"));

        $partial = File::get("$install/views/sets/fine_builder.antlers.html");
        $this->assertStringContainsString('src="partials/sections/{type}"', $partial);
        $this->assertStringNotContainsString('sets/blocks', $partial);
    }

    #[Test]
    public function it_keeps_customised_files_unless_forced(): void
    {
        InstallBuilderFiles::execute(targets: $this->targets());
        $hero = $this->site.'/install/views/sets/blocks/hero.antlers.html';
        File::put($hero, 'customised');

        $result = InstallBuilderFiles::execute(targets: $this->targets());
        $this->assertEmpty($result['created']);
        $this->assertSame('customised', File::get($hero));

        InstallBuilderFiles::execute(true, $this->targets());
        $this->assertNotSame('customised', File::get($hero));
    }

    #[Test]
    public function every_builder_set_has_a_fieldset_blueprint_and_template(): void
    {
        $sets = collect(\Statamic\Facades\YAML::file(InstallBuilderFiles::stubPath('fieldsets/fine_builder.yaml'))->parse()['fields'][0]['field']['sets'])
            ->flatMap(fn ($group) => array_keys($group['sets']))
            ->reject(fn ($set) => $set === 'block');

        $this->assertNotEmpty($sets);

        foreach ($sets as $set) {
            $this->assertFileExists(InstallBuilderFiles::stubPath("fieldsets/block_$set.yaml"));
            $this->assertFileExists(InstallBuilderFiles::stubPath("blueprints/blocks/$set.yaml"));
            $this->assertFileExists(InstallBuilderFiles::stubPath("views/sets/blocks/$set.antlers.html"));
        }
    }
}
