<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Builder field
    |--------------------------------------------------------------------------
    |
    | Handle of the fieldset (and of the Replicator field inside it) that holds
    | the page blocks. Collections get it with `import: <handle>`.
    |
    */

    'field' => 'fine_builder',

    /*
    |--------------------------------------------------------------------------
    | Block names
    |--------------------------------------------------------------------------
    |
    | One name ties each block together: fieldset <fieldset_prefix><name>, a
    | blueprint <name> in the reusable blocks collection, and the template
    | <views>/<name>. Set these before installing: installed and scaffolded
    | files use them, and existing files are not renamed.
    |
    */

    'collection' => 'blocks',

    'fieldset_prefix' => 'block_',

    'views' => 'sets/blocks',

    /*
    |--------------------------------------------------------------------------
    | Template
    |--------------------------------------------------------------------------
    |
    | Template assigned to a collection when the builder is enabled on it from
    | the Fine Builder page (or `php please fine-builder:install --collection=`).
    | The installed one renders the blocks and falls back to `{{ content }}`.
    |
    */

    'template' => 'fine_builder',

    /*
    |--------------------------------------------------------------------------
    | Visual editing
    |--------------------------------------------------------------------------
    |
    | Inject the visual editing overlay into Live Preview responses. Turn off
    | to place {{ fine_builder_visual:script }} in your layout yourself.
    |
    */

    'inject_overlay' => true,

];
