<?php

namespace AliAwwad\FineBuilder\Tags;

use Statamic\Tags\Tags;

/**
 * Markup hooks for visual editing. Every method outputs nothing outside Live Preview,
 * so the public site stays clean.
 *
 *   {{ fine_builder_visual:root }}                         on the element wrapping the builder loop
 *   {{ fine_builder_visual:attrs :id="id" :type="type" }}  on each set's wrapper (edit="..." for reusable blocks)
 *   {{ fine_builder_visual:field path="heading.text" }}    on an inline-editable text element
 *   {{ fine_builder_visual:open path="image" }}            on an element whose click opens that field in the form
 *   {{ fine_builder_visual:script }}                       before </body> (only when fine-builder.inject_overlay is off)
 */
class FineBuilderVisual extends Tags
{
    public function root(): string
    {
        return $this->live() ? ' data-fbv-root' : '';
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
