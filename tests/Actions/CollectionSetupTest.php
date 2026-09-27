<?php

namespace AliAwwad\FineBuilder\Tests\Actions;

use AliAwwad\FineBuilder\Actions\DisableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\EnableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\GetCollectionsWithFineBuilder;
use AliAwwad\FineBuilder\Actions\GetEntriesWithOwnTemplate;
use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class CollectionSetupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::make('pages')->title('Pages')->save();
        Collection::make('blocks')->title('Blocks')->save();

        Blueprint::make('page')->setNamespace('collections.pages')->setContents([
            'tabs' => [
                'main' => ['sections' => [['fields' => [
                    ['handle' => 'title', 'field' => ['type' => 'text']],
                    ['handle' => 'content', 'field' => ['type' => 'markdown']],
                ]]]],
            ],
        ])->save();
    }

    private function blueprint(): \Statamic\Fields\Blueprint
    {
        return Blueprint::find('collections.pages.page');
    }

    #[Test]
    public function enabling_adds_the_builder_after_the_title_and_the_bridge_to_the_sidebar(): void
    {
        EnableFineBuilderOnCollection::execute('pages');

        $contents = $this->blueprint()->contents();
        $main = $contents['tabs']['main']['sections'][0]['fields'];

        $this->assertSame('title', $main[0]['handle']);
        $this->assertSame(['import' => 'fine_builder'], $main[1]);
        $this->assertSame('content', $main[2]['handle']);
        $this->assertSame('fine_builder_bridge', $contents['tabs']['sidebar']['sections'][0]['fields'][0]['field']['type']);
        $this->assertTrue($this->blueprint()->hasField('fine_builder'));
    }

    #[Test]
    public function enabling_updates_live_preview_in_place_and_sets_the_template(): void
    {
        EnableFineBuilderOnCollection::execute('pages');

        $collection = Collection::findByHandle('pages');
        $this->assertSame('fine_builder', $collection->template());
        $this->assertFalse($collection->basePreviewTargets()->first()['refresh']);
    }

    #[Test]
    public function enabling_keeps_a_custom_template_and_is_idempotent(): void
    {
        Collection::findByHandle('pages')->template('landing')->save();

        EnableFineBuilderOnCollection::execute('pages');
        EnableFineBuilderOnCollection::execute('pages');

        $fields = collect($this->blueprint()->contents()['tabs'])->flatMap(fn ($t) => $t['sections'][0]['fields']);
        $this->assertCount(1, $fields->where('import', 'fine_builder'));
        $this->assertCount(1, $fields->where('handle', 'visual_editing'));
        $this->assertSame('landing', Collection::findByHandle('pages')->template());
    }

    #[Test]
    public function disabling_removes_the_builder_and_restores_the_default_template(): void
    {
        EnableFineBuilderOnCollection::execute('pages');
        DisableFineBuilderOnCollection::execute('pages');

        $this->assertFalse($this->blueprint()->hasField('fine_builder'));
        $this->assertFalse($this->blueprint()->hasField('visual_editing'));
        $this->assertTrue($this->blueprint()->hasField('content'));
        $this->assertSame('default', Collection::findByHandle('pages')->template());
    }

    #[Test]
    public function collections_are_listed_without_blocks_and_flagged(): void
    {
        EnableFineBuilderOnCollection::execute('pages');

        $collections = GetCollectionsWithFineBuilder::execute();

        $this->assertSame(['pages'], $collections->map->handle()->all());
        $this->assertTrue($collections->first()->hasFineBuilder);
    }

    #[Test]
    public function entries_overriding_the_template_are_reported(): void
    {
        EnableFineBuilderOnCollection::execute('pages');

        Entry::make()->collection('pages')->slug('a')->data(['title' => 'A', 'template' => 'default'])->save();
        Entry::make()->collection('pages')->slug('b')->data(['title' => 'B', 'template' => 'fine_builder'])->save();
        Entry::make()->collection('pages')->slug('c')->data(['title' => 'C'])->save();

        $this->assertSame(['a'], GetEntriesWithOwnTemplate::execute('pages')->map->slug()->all());

        $this->artisan('fine-builder:install', ['--collection' => ['pages']])
            ->expectsOutputToContain('1 entries set their own template (default)')
            ->assertSuccessful();
    }
}
