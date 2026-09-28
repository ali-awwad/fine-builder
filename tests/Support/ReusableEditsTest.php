<?php

namespace AliAwwad\FineBuilder\Tests\Support;

use AliAwwad\FineBuilder\Support\ReusableEdits;
use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

class ReusableEditsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::make('pages')->save();
        Collection::make('blocks')->save();

        $blueprint = fn ($namespace, $fields) => Blueprint::make(explode('.', $namespace)[2])
            ->setNamespace(implode('.', array_slice(explode('.', $namespace), 0, 2)))
            ->setContents(['tabs' => ['main' => ['sections' => [['fields' => $fields]]]]])
            ->save();

        $blueprint('collections.pages.page', [
            ['handle' => 'title', 'field' => ['type' => 'text']],
            ['handle' => 'visual_editing', 'field' => ['type' => 'fine_builder_bridge']],
        ]);
        $blueprint('collections.blocks.cta', [
            ['handle' => 'title', 'field' => ['type' => 'text']],
            ['handle' => 'heading', 'field' => ['type' => 'group', 'fields' => [['handle' => 'text', 'field' => ['type' => 'text']]]]],
            ['handle' => 'buttons', 'field' => ['type' => 'replicator']],
        ]);

        Entry::make()->collection('blocks')->id('cta')->slug('cta')->blueprint('cta')
            ->data(['title' => 'CTA', 'heading' => ['text' => 'Old'], 'buttons' => [['label' => 'Go']]])->save();
    }

    private function page(array $edits): \Statamic\Contracts\Entries\Entry
    {
        return Entry::make()->collection('pages')->id('home')->slug('home')->blueprint('page')
            ->data(['title' => 'Home', 'visual_editing' => $edits]);
    }

    #[Test]
    public function it_only_changes_text_in_existing_fields(): void
    {
        $block = ReusableEdits::apply(Entry::find('cta'), [
            'heading.text' => 'New',
            'buttons' => 'not an array',
            'missing.text' => 'x',
            'buttons.0.label' => 'Go now',
        ]);

        $this->assertSame(['text' => 'New'], $block->get('heading'));
        $this->assertSame([['label' => 'Go now']], $block->get('buttons'));
        $this->assertNull($block->get('missing'));
    }

    #[Test]
    public function saving_the_page_saves_the_edits_to_the_blocks_and_not_to_the_page(): void
    {
        $this->actingAs(User::make()->makeSuper()->save());

        $this->page(['cta' => ['heading.text' => 'New']])->save();

        $this->assertSame('New', Entry::find('cta')->get('heading')['text']);
        $this->assertFalse(Entry::find('home')->has('visual_editing'));
    }

    #[Test]
    public function edits_are_dropped_without_permission_to_edit_the_block(): void
    {
        $this->page(['cta' => ['heading.text' => 'New']])->save();

        $this->assertSame('Old', Entry::find('cta')->get('heading')['text']);
    }

    #[Test]
    public function preview_shows_the_edits_without_saving_them(): void
    {
        ReusableEdits::preview(['cta' => ['heading.text' => 'New']]);

        $this->assertSame('New', Entry::find('cta')->get('heading')['text']);
        $this->assertSame('New', Entry::query()->where('collection', 'blocks')->first()->get('heading')['text']);
        $this->assertStringContainsString('Old', file_get_contents(Entry::find('cta')->path()));
    }
}
