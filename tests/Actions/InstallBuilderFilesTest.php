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
