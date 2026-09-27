<?php

namespace AliAwwad\FineBuilder\Tests\Actions;

use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use AliAwwad\FineBuilder\Actions\MakeBlock;
use AliAwwad\FineBuilder\Support\BuilderFieldset;
use AliAwwad\FineBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;

class MakeBlockTest extends TestCase
{
    #[Test]
    public function it_creates_the_fieldset_template_blueprint_and_builder_set(): void
    {
        MakeBlock::execute('logo_cloud');

        $this->assertFileExists($this->site.'/fieldsets/block_logo_cloud.yaml');
        $this->assertFileExists($this->site.'/views/sets/blocks/logo_cloud.antlers.html');
        $this->assertFileExists($this->site.'/blueprints/collections/blocks/logo_cloud.yaml');

        $set = BuilderFieldset::sets(BuilderFieldset::siteContents())['logo_cloud'];
        $this->assertSame('blocks', $set['group']);
        $this->assertSame('Logo Cloud', $set['set']['display']);
        $this->assertSame([['import' => 'block_logo_cloud']], $set['set']['fields']);

        // The new fieldset and blueprint resolve, including the common fields they reference.
        $this->assertTrue(Fieldset::find('block_logo_cloud')->fields()->has('buttons'));
        $this->assertTrue(Blueprint::find('collections.blocks.logo_cloud')->hasField('heading'));
    }

    #[Test]
    public function the_template_renders_with_editing_hooks_only_in_live_preview(): void
    {
        InstallBuilderFiles::execute(targets: ['views' => $this->site.'/views']);
        MakeBlock::execute('testimonials', "Customers' love");

        $data = ['heading' => ['text' => 'Loved'], 'text' => 'Great product'];

        $this->fakeLivePreview(false);
        $html = view('sets.blocks.testimonials', $data)->render();
        $this->assertStringContainsString('Great product', $html);
        $this->assertStringNotContainsString('data-fbv', $html);

        $this->fakeLivePreview();
        $this->assertStringContainsString('data-fbv-field="text" data-fbv-multiline', view('sets.blocks.testimonials', $data)->render());

        $this->assertStringContainsString("Customers' love", File::get($this->site.'/views/sets/blocks/testimonials.antlers.html'));
        $this->assertSame("Block Customers' love", Fieldset::find('block_testimonials')->title());
    }

    #[Test]
    public function a_new_group_is_created_before_the_reusable_group(): void
    {
        MakeBlock::execute('faq', group: 'marketing', reusable: false);

        $groups = array_keys(BuilderFieldset::groups(BuilderFieldset::siteContents()));
        $this->assertSame(['blocks', 'marketing', 'reusable'], $groups);
        $this->assertFileDoesNotExist($this->site.'/blueprints/collections/blocks/faq.yaml');
    }

    #[Test]
    public function it_rejects_invalid_reserved_and_existing_handles(): void
    {
        foreach (['Logo-Cloud', 'block', 'hero'] as $handle) {
            try {
                MakeBlock::execute($handle);
                $this->fail("[$handle] should have been rejected.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertFileDoesNotExist($this->site.'/fieldsets/block_Logo-Cloud.yaml');
    }

    #[Test]
    public function it_works_from_the_command_line(): void
    {
        $this->artisan('fine-builder:make-block', ['handle' => 'team', '--display' => 'Our team'])
            ->expectsOutputToContain('views/sets/blocks/team.antlers.html')
            ->assertSuccessful();

        $this->artisan('fine-builder:make-block', ['handle' => 'team'])->assertFailed();
    }
}
