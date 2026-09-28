<?php

namespace AliAwwad\FineBuilder\Tags;

use AliAwwad\FineBuilder\Support\ReusableEdits;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

/**
 * Markup hooks for visual editing, as {{ fbv:... }} (or the long {{ fine_builder_visual:... }}).
 * Every method outputs nothing outside Live Preview, so the public site stays clean.
 *
 *   {{ fbv:root }}                         on the element wrapping the builder loop
 *   {{ fbv:attrs :id="id" :type="type" }}  on each set's wrapper (edit="..." entry="..." for reusable blocks)
 *   {{ fbv:field path="heading.text" }}    on an inline-editable text element (multiline="true" for textareas)
 *   {{ fbv:open path="image" }}            on an element whose click opens that field in the form
 *   {{ fbv:script }}                       before </body> (only when fine-builder.inject_overlay is off)
 *
 * Paths are relative to the set: "items.{index}.title" inside a grid loop. prefix="..." prepends
 * a path, for partials rendering data nested in the set.
 */
class FineBuilderVisual extends Tags
{
    protected static $aliases = ['fbv'];

    public function root(): string
    {
        if (! $this->live()) {
            return '';
        }

        // Before the blocks render: show the page's unsaved reusable block edits.
        if ($entry = Entry::find((string) $this->context->raw('id'))) {
            ReusableEdits::preview(ReusableEdits::of($entry));
        }

        return ' data-fbv-root';
    }

    public function attrs(): string
    {
        if (! $this->live()) {
            return '';
        }

        $attrs = [
            'data-fbv-set' => (string) $this->params->get('id'),
            'data-fbv-type' => (string) $this->params->get('type'),
        ];

        if ($edit = $this->params->get('edit')) {
            $attrs['data-fbv-reusable'] = (string) $edit;
        }

        if ($entry = $this->params->get('entry')) {
            $attrs['data-fbv-entry'] = (string) $entry;
        }

        return collect($attrs)->map(fn ($value, $key) => $key.'="'.e($value).'"')->prepend('')->implode(' ');
    }

    public function field(): string
    {
        if (! $this->live()) {
            return '';
        }

        $path = $this->params->get('prefix', '').$this->params->get('path');

        return ' data-fbv-field="'.e($path).'"'.($this->params->bool('multiline') ? ' data-fbv-multiline' : '');
    }

    public function open(): string
    {
        if (! $this->live()) {
            return '';
        }

        return ' data-fbv-open="'.e($this->params->get('prefix', '').$this->params->get('path')).'"';
    }

    public function script(): string
    {
        return $this->live() ? static::overlay() : '';
    }

    /** The overlay's inline <style> + <script>, marked so it's never injected twice. */
    public static function overlay(): string
    {
        $dir = __DIR__.'/../../resources/preview';

        return '<style data-fbv-overlay>'.file_get_contents("$dir/overlay.css").'</style>'
            .'<script data-fbv-overlay>'.file_get_contents("$dir/overlay.js").'</script>';
    }

    protected function live(): bool
    {
        return request()->isLivePreview();
    }
}
