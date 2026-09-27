<?php

namespace AliAwwad\FineBuilder\Tests\Tags;

use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Antlers;

class FineBuilderVisualTagTest extends TestCase
{
    /** Rendered as a trusted template: untrusted (user content) Antlers only runs allowlisted tags. */
    private function render(string $template, array $data = []): string
    {
        return (string) Antlers::parse($template, $data, true);
    }

    #[Test]
    public function it_outputs_nothing_outside_live_preview(): void
    {
        $this->fakeLivePreview(false);

        $output = $this->render(
            '<main{{ fine_builder_visual:root }}><div{{ fine_builder_visual:attrs id="a" type="hero" }}><h2{{ fine_builder_visual:field path="heading.text" }}></h2><img{{ fine_builder_visual:open path="image" }}></div></main>{{ fine_builder_visual:script }}'
        );

        $this->assertSame('<main><div><h2></h2><img></div></main>', $output);
    }

    #[Test]
    public function it_outputs_editing_hooks_in_live_preview(): void
    {
        $this->fakeLivePreview();

        $this->assertSame(' data-fbv-root', $this->render('{{ fine_builder_visual:root }}'));
        $this->assertSame(' data-fbv-set="a1" data-fbv-type="hero"', $this->render('{{ fine_builder_visual:attrs id="a1" type="hero" }}'));
        $this->assertSame(
            ' data-fbv-set="b1" data-fbv-type="block" data-fbv-reusable="/cp/edit"',
            $this->render('{{ fine_builder_visual:attrs id="b1" type="block" edit="/cp/edit" }}')
        );
        $this->assertSame(' data-fbv-open="image"', $this->render('{{ fine_builder_visual:open path="image" }}'));
    }

    #[Test]
    public function field_paths_take_a_prefix_and_multiline_flag(): void
    {
        $this->fakeLivePreview();

        $this->assertSame(
            ' data-fbv-field="tiers.0.buttons.1.label"',
            $this->render('{{ fine_builder_visual:field prefix="tiers.0." path="buttons.1.label" }}')
        );
        $this->assertSame(
            ' data-fbv-field="heading.subheading" data-fbv-multiline',
            $this->render('{{ fine_builder_visual:field path="heading.subheading" multiline="true" }}')
        );
    }

    #[Test]
    public function attribute_values_are_escaped(): void
    {
        $this->fakeLivePreview();

        $this->assertSame(' data-fbv-open="&quot;&gt;&lt;x"', $this->render('{{ fine_builder_visual:open :path="p" }}', ['p' => '"><x']));
    }

    #[Test]
    public function fbv_is_a_short_alias(): void
    {
        $this->fakeLivePreview();

        $this->assertSame(' data-fbv-field="title"', $this->render('{{ fbv:field path="title" }}'));
    }
}
