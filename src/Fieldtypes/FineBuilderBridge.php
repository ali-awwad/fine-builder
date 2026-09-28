<?php

namespace AliAwwad\FineBuilder\Fieldtypes;

use Statamic\Facades\Fieldset;
use Statamic\Fields\Field;
use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;

/**
 * Invisible helper that connects the Live Preview canvas to the entry form.
 * It reads/writes the builder field through the publish container. Its own value holds the
 * pending reusable block edits (see ReusableEdits), which saving the entry moves to the blocks.
 */
class FineBuilderBridge extends Fieldtype
{
    protected static $handle = 'fine_builder_bridge';

    protected $categories = ['special'];

    protected $icon = 'preview';

    protected function configFieldItems(): array
    {
        return [
            'field' => [
                'display' => __('Builder field'),
                'instructions' => __('Handle of the Replicator field edited visually.'),
                'type' => 'text',
                'default' => 'fine_builder',
            ],
        ];
    }

    /** Pending edits come back only from a working copy (revisions): a real save removes them. */
    public function preProcess($data)
    {
        return is_array($data) && $data ? $data : null;
    }

    public function process($data)
    {
        return is_array($data) && $data ? $data : null;
    }

    public function preload()
    {
        $handle = $this->config('field', config('fine-builder.field', 'fine_builder'));

        return [
            'field' => $handle,
            'sets' => $this->setGroups($handle),
        ];
    }

    /** Set groups of the builder field, as [{display, sets: [{handle, display, instructions, fields: [{handle, display, type}]}]}]. */
    protected function setGroups(string $handle): array
    {
        $parent = $this->field()?->parent();
        $field = $parent && method_exists($parent, 'blueprint') ? $parent->blueprint()?->field($handle) : null;
        $field ??= Fieldset::find($handle)?->field($handle);

        if (! $field) {
            return [];
        }

        return collect($field->config()['sets'] ?? [])->map(fn ($group, $groupHandle) => [
            'display' => __($group['display'] ?? $groupHandle),
            'sets' => collect($group['sets'] ?? [])->map(fn ($set, $setHandle) => [
                'handle' => $setHandle,
                'display' => __($set['display'] ?? $setHandle),
                'instructions' => __($set['instructions'] ?? ''),
                'fields' => (new Fields($set['fields'] ?? []))->all()->map(fn (Field $field) => [
                    'handle' => $field->handle(),
                    'display' => __($field->display()),
                    'type' => $field->type(),
                ])->values()->all(),
            ])->values()->all(),
        ])->values()->all();
    }
}
