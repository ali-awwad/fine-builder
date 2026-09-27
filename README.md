# Fine Builder

> Fine Builder is a Statamic page builder with visual editing. Pages are built from blocks, and editors work on the page itself in Live Preview, the way they would in Elementor, Wix or Webflow.

**Requires** PHP 8.3+ and Statamic 6.

## Features

**Page builder**

- A `fine_builder` Replicator with ready-made blocks: Hero, Carousel, Content, Custom HTML, Features, Stats, Speakers, Pricing and CTA.
- Shared "common" fields (heading, buttons, content, custom HTML) that every block reuses, so a change to buttons happens once.
- Reusable blocks: a **Blocks** collection whose entries can be inserted on any page with the "Block" set.
- One name ties each block together: fieldset `block_<name>`, set `<name>`, Blocks blueprint `<name>` and template `sets/blocks/<name>`.
- A `themeicons` icon set for icon fields.

**Visual editing in Live Preview**

- Hover outlines each block. Click one to open and scroll to it in the form. Selecting a set in the form highlights it on the page.
- Block toolbar: move up/down, duplicate, hide, delete, and add a block below from a searchable list.
- Edit headings, subheadings, button labels and item text in place.
- Click an image, icon, button or pricing card to open that exact field in the form, with native pickers. **Block settings** lists every field of a block, including ones you can't click, such as a background video.
- Live Preview updates only the blocks that changed, without reloading the page.
- The form stays the source of truth: validation, revisions, permissions and saving all work as usual. Nothing is added to the public site.

## Installation

```bash
composer require ali-awwad/fine-builder
```

Then open **Tools → Fine Builder** in the Control Panel:

1. **Install builder files.** This copies the fieldsets, block templates, icons and the Blocks collection into your site (`resources/fieldsets`, `resources/views/sets`, `resources/themeicons`, `content/collections/blocks.yaml`). They are yours to restyle and extend. Existing files are kept.
2. **Select collections** to use the builder on. Each selected collection gets the builder next to its title and a "Visual editing" helper in the sidebar. Live Preview is set to update without reloading, and the collection switches to the `fine_builder` template if it still uses `default`.
3. Run `npm run build` so Tailwind generates the block styles. The templates use Tailwind v4 classes and expect `@source "../views"` in your CSS.

The same from the command line:

```bash
php please fine-builder:install --collection=pages
```

## Rendering the blocks

Your templates don't know about the builder until one of them includes it. Blocks render wherever a template has:

```antlers
{{ partial:sets/fine_builder }}
```

There are three ways to set that up:

1. **The installed `fine_builder` template (the default).** It renders the blocks, and shows the entry's `{{ content }}` for entries that don't have blocks yet. When you enable a collection that still uses `default`, it switches to this template automatically.
2. **Your own template.** If the collection keeps its own template (e.g. `default.antlers.html` or `page.antlers.html`), add the partial where the blocks should go:

   ```antlers
   <h1>{{ title }}</h1>
   {{ partial:sets/fine_builder }}
   ```

   The Setup page warns when an enabled collection uses a template other than `fine_builder`, so you can check it includes the partial.
3. **Per entry.** An entry's Template field overrides the collection's template. Sites that saved entries with `template: default` keep that template for those entries, even after the collection switches. Set it to `fine_builder` on those entries, or add the partial to their template. `fine-builder:install --collection=…` and the Setup page both list how many entries do this.

The partial works inside any layout, and Live Preview's visual editing works wherever it's included. For content above the blocks for a specific collection, such as an event date, the `fine_builder` template includes `headers/<collection>.antlers.html` when it exists.

## Adding your own blocks

Blocks live in your site, not in the addon, so you never edit the addon to add or change one. The quickest way to start one:

```bash
php please fine-builder:make-block testimonials
# --display="Customer stories"   name shown to editors
# --group=marketing              group in the block picker (created if needed)
# --no-reusable                  skip the Blocks collection blueprint
```

This creates:

- `resources/fieldsets/block_testimonials.yaml`, with heading, text and buttons fields to start from.
- `resources/views/sets/blocks/testimonials.antlers.html`, with the visual editing hooks already in place.
- `resources/blueprints/collections/blocks/testimonials.yaml`, so it can be used as a reusable block too.

It also adds a `testimonials` set to `resources/fieldsets/fine_builder.yaml`. Visual editing picks the new block up straight away: it appears in the add-block list, its toolbar and its settings menu.

By hand, the convention is: fieldset `block_<name>`, a set `<name>` in `fine_builder.yaml` containing only `- import: block_<name>`, blueprint `blocks/<name>.yaml`, and template `sets/blocks/<name>.antlers.html`.

## Updating

Installing never overwrites your files, and never changes your `fine_builder.yaml`. When an addon update ships new blocks, **Tools → Fine Builder** lists them under **New blocks available**. Adding them copies their files (existing ones are kept) and adds them to your block list, in the same group they have in the addon.

## Making your own templates editable

The visual editing hooks output nothing outside Live Preview:

```antlers
<div{{ fine_builder_visual:root }}>                       {{# wraps the builder loop (already in sets/fine_builder) #}}
<div{{ fine_builder_visual:attrs :id="id" :type="type" }}> {{# wraps each set #}}
<h2{{ fine_builder_visual:field path="heading.text" }}>    {{# text edited in place #}}
<img{{ fine_builder_visual:open path="image" }} ...>        {{# click opens the field #}}
```

`path` is the field's path inside the set, for example `items.{index}.title` inside a grid loop. Add `multiline="true"` for textareas. Partials that render nested data take a `prefix`, as the buttons partial does with `fbv_prefix`.

## Configuration

```bash
php artisan vendor:publish --tag=fine-builder-config
```

- `field`: the builder fieldset/field handle (default `fine_builder`).
- `template`: the template assigned when the builder is enabled on a collection.
- `inject_overlay`: adds the visual editing script to Live Preview pages automatically. Turn it off to place `{{ fine_builder_visual:script }}` in your layout yourself.

## Security

The Custom HTML block outputs its content unescaped by design. Only give trusted roles access to it.
