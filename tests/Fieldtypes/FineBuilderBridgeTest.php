<?php

namespace AliAwwad\FineBuilder\Tests\Fieldtypes;

use AliAwwad\FineBuilder\Fieldtypes\FineBuilderBridge;
use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Fields\Field;

class FineBuilderBridgeTest extends TestCase
{
    private function fieldtype(): FineBuilderBridge
    {
        return (new Field('visual_editing', ['type' => 'fine_builder_bridge']))->fieldtype();
    }

    #[Test]
    public function it_only_keeps_pending_reusable_edits(): void
    {
        $this->assertNull($this->fieldtype()->process('anything'));
        $this->assertNull($this->fieldtype()->process([]));
        $this->assertSame(['cta' => ['heading.text' => 'New']], $this->fieldtype()->process(['cta' => ['heading.text' => 'New']]));
        $this->assertNull($this->fieldtype()->preProcess('anything'));
        $this->assertSame(['cta' => ['heading.text' => 'New']], $this->fieldtype()->preProcess(['cta' => ['heading.text' => 'New']]));
    }

    #[Test]
    public function it_preloads_the_builder_sets_with_their_fields(): void
    {
        $meta = $this->fieldtype()->preload();

        $this->assertSame('fine_builder', $meta['field']);

        $sets = collect($meta['sets'])->flatMap(fn ($group) => $group['sets'])->keyBy('handle');
        $this->assertSame('Hero', $sets['hero']['display']);
        $this->assertContains('background_video', array_column($sets['hero']['fields'], 'handle'));
        $this->assertArrayHasKey('block', $sets->all());
    }
}
