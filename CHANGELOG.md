# Changelog

All notable changes to Fine Builder are documented here.

## Unreleased

### Security

- **Tools → Fine Builder** now requires the new "Configure Fine Builder" permission. The setup page and its install, add-blocks and collections actions check it on the server, and the nav item is hidden from users without it. Super users keep access; give the permission to other roles under **Users → Roles → Fine Builder**.

### Added

- Changelog, support route (GitHub issues) and third-party notices for the Lucide/Feather-based `themeicons` set.

## v0.1.4 - 2026-09-28

### Added

- Reusable blocks can be edited in place in Live Preview. The edits wait in the page's form and are written to the block entries when the page is saved, only for blocks the user may edit. With revisions on, they are kept in the working copy until the page is published.

## v0.1.3 - 2026-09-28

### Fixed

- The `field` and `template` settings now also rename the builder fieldset, the `sets/<field>` partial and the page template on install, along with the Replicator handle and loop inside them.
- Adding stock blocks works again when `field` isn't `fine_builder`.

## v0.1.2 - 2026-09-28

### Added

- `collection`, `fieldset_prefix` and `views` settings to rename the reusable blocks collection, the block fieldset prefix and the block templates folder. Installing, adding stock blocks and `make-block` use them.

## v0.1.1 - 2026-09-27

### Changed

- README wording.

## v0.1.0 - 2026-09-27

Initial release.

- `fine_builder` Replicator with stock blocks (Hero, Carousel, Content, Custom HTML, Features, Stats, Speakers, Pricing, CTA), shared common fields, a `themeicons` icon set and a Blocks collection for reusable blocks.
- Visual editing in Live Preview: block outlines and toolbar, adding blocks from the page, text edited in place, and images, icons, buttons, rich text and custom HTML fields opened from the page.
- **Tools → Fine Builder** setup page and `fine-builder:install` command.
- `fine-builder:make-block` command, and new stock blocks offered on the setup page after updates.
- Blocks render from any template with `{{ partial:sets/fine_builder }}`.
- Headings choose their HTML tag (H1–H6); `{{ fbv:... }}` alias for the visual editing hooks.
