<?php

namespace AliAwwad\FineBuilder\Tests\Views;

use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Fields\LabeledValue;

class BuilderTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        InstallBuilderFiles::execute(targets: ['views' => $this->site.'/views']);
    }

    private function stats(): array
    {
        return [['id' => 's1', 'type' => 'stats', 'enabled' => true, 'heading' => ['text' => 'Numbers'], 'stats' => [['value' => '42', 'label' => 'Answers']]]];
    }

    #[Test]
    public function the_partial_renders_blocks_in_any_template(): void
    {
        $this->fakeLivePreview(false);

        $html = view('sets.fine_builder', ['fine_builder' => $this->stats()])->render();

        $this->assertStringContainsString('Numbers', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringNotContainsString('data-fbv', $html);
    }

    #[Test]
    public function in_live_preview_the_partial_marks_the_root_and_sets_even_when_empty(): void
    {
        $this->fakeLivePreview();

        $this->assertStringContainsString('data-fbv-set="s1" data-fbv-type="stats"', view('sets.fine_builder', ['fine_builder' => $this->stats()])->render());
        $this->assertStringContainsString('<div data-fbv-root>', view('sets.fine_builder', ['fine_builder' => []])->render());
    }

    #[Test]
    public function the_template_shows_legacy_content_only_without_blocks(): void
    {
        $this->fakeLivePreview(false);

        $this->assertStringContainsString('Old body', view('fine_builder', ['content' => 'Old body', 'fine_builder' => []])->render());

        $html = view('fine_builder', ['content' => 'Old body', 'fine_builder' => $this->stats()])->render();
        $this->assertStringNotContainsString('Old body', $html);
        $this->assertStringContainsString('Numbers', $html);
    }

    #[Test]
    public function the_heading_tag_sets_level_and_size_and_falls_back_to_the_block_default(): void
    {
        $this->fakeLivePreview(false);
        $render = fn (array $heading, array $params = []) => view('sets.common.heading', ['heading' => $heading] + $params)->render();

        // Button groups augment to LabeledValue, empty when the editor leaves the level unset.
        $this->assertMatchesRegularExpression('/<h3 class="[^"]*text-2xl/', $render(['text' => 'Hi', 'tag' => new LabeledValue('h3', 'H3')]));
        $this->assertMatchesRegularExpression('/<h1 class/', $render(['text' => 'Hi', 'tag' => new LabeledValue(null, null)], ['tag' => 'h1']));
        $this->assertMatchesRegularExpression('/<h2 class="[^"]*text-3xl sm:text-4xl[^"]*">\s*Hi\s*<\/h2>/', $render(['text' => 'Hi']));
        $this->assertMatchesRegularExpression('/<h1 class="[^"]*text-5xl/', $render(['text' => 'Hi'], ['tag' => 'h1']));
        $this->assertMatchesRegularExpression('/<h4 class="[^"]*text-xl sm:text-2xl/', $render(['text' => 'Hi', 'tag' => new LabeledValue('h4', 'H4')], ['tag' => 'h1']));
        $this->assertStringContainsString('mx-auto text-center', $render(['text' => 'Hi', 'subheading' => 'Sub', 'alignment' => 'center']));
    }
}
