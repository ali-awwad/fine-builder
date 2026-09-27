<?php

namespace AliAwwad\FineBuilder\Listeners;

use AliAwwad\FineBuilder\Tags\FineBuilderVisual;
use Statamic\Events\ResponseCreated;

/**
 * Adds the visual editing overlay to Live Preview pages, so layouts don't need
 * {{ fine_builder_visual:script }}. Pages without builder markup are left alone.
 */
class InjectVisualEditing
{
    public function handle(ResponseCreated $event): void
    {
        if (! config('fine-builder.inject_overlay', true) || ! request()->isLivePreview()) {
            return;
        }

        $content = $event->response->getContent();

        if (! is_string($content)
            || ! str_contains($content, 'data-fbv-root')
            || str_contains($content, 'data-fbv-overlay')
            || ($position = strripos($content, '</body>')) === false) {
            return;
        }

        $event->response->setContent(substr_replace($content, FineBuilderVisual::overlay(), $position, 0));
    }
}
