<?php

namespace AliAwwad\FineBuilder\Tests\Listeners;

use AliAwwad\FineBuilder\Listeners\InjectVisualEditing;
use AliAwwad\FineBuilder\Tests\TestCase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\ResponseCreated;

class InjectVisualEditingTest extends TestCase
{
    private function handle(string $html): string
    {
        $response = new Response($html);
        (new InjectVisualEditing)->handle(new ResponseCreated($response, null));

        return $response->getContent();
    }

    #[Test]
    public function it_injects_the_overlay_into_builder_pages_in_live_preview(): void
    {
        $this->fakeLivePreview();

        $html = $this->handle('<body><main data-fbv-root></main></body>');

        $this->assertStringContainsString('<script data-fbv-overlay>', $html);
        $this->assertStringEndsWith('</script></body>', $html);
    }

    #[Test]
    public function it_leaves_other_responses_alone(): void
    {
        $this->fakeLivePreview(false);
        $this->assertSame('<body><main data-fbv-root></main></body>', $this->handle('<body><main data-fbv-root></main></body>'));

        $this->fakeLivePreview();
        $this->assertSame('<body><main></main></body>', $this->handle('<body><main></main></body>'));
    }

    #[Test]
    public function it_does_not_inject_twice_or_when_disabled(): void
    {
        $this->fakeLivePreview();

        $once = $this->handle('<body><main data-fbv-root></main></body>');
        $this->assertSame($once, $this->handle($once));

        config(['fine-builder.inject_overlay' => false]);
        $this->assertSame('<body><main data-fbv-root></main></body>', $this->handle('<body><main data-fbv-root></main></body>'));
    }
}
