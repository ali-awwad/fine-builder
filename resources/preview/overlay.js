/**
 * Fine Builder Visual — Live Preview side. Injected by {{ fine_builder_visual:script }} only
 * during Live Preview. Draws block outlines + toolbar and in-place text editing, and sends
 * every change to the CP bridge (fine_builder_bridge fieldtype), which updates the form.
 */
(() => {
    if (window.parent === window || window.__fbv) return;
    window.__fbv = true;

    const state = { sets: [], labels: {}, fields: {}, readOnly: false, hovered: null, selected: null, editing: null };
    const send = (action, data = {}) => window.parent.postMessage({ source: 'fbv-preview', action, ...data }, '*');

    const icon = (d) =>
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
    const icons = {
        up: icon('<path d="m18 15-6-6-6 6"/>'),
        down: icon('<path d="m6 9 6 6 6-6"/>'),
        duplicate: icon('<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>'),
        hide: icon('<path d="M9.9 4.2A10 10 0 0 1 22 12a13 13 0 0 1-1.7 2.7M6.6 6.6A13 13 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6M2 2l20 20M9.9 9.9a3 3 0 0 0 4.2 4.2"/>'),
        remove: icon('<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>'),
        add: icon('<path d="M12 5v14M5 12h14"/>'),
        edit: icon('<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>'),
        settings: icon('<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>'),
        external: icon('<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>'),
    };

    // ---- UI chrome -------------------------------------------------------------------------
    const el = (tag, cls, html = '') => Object.assign(document.createElement(tag), { className: cls, innerHTML: html });
    const hoverBox = el('div', 'fbv-box fbv-box--hover');
    const selectBox = el('div', 'fbv-box fbv-box--selected');
    const toolbar = el('div', 'fbv-toolbar');
    const picker = el('div', 'fbv-picker');
    document.body.append(hoverBox, selectBox, toolbar, picker);

    const blocks = () => [...document.querySelectorAll('[data-fbv-set]')];
    const blockById = (id) => document.querySelector(`[data-fbv-set="${CSS.escape(id)}"]`);

    function place(box, target) {
        if (!target) return box.classList.remove('is-visible');
        const r = target.getBoundingClientRect();
        Object.assign(box.style, { top: `${r.top + scrollY}px`, left: `${r.left + scrollX}px`, width: `${r.width}px`, height: `${r.height}px` });
        box.classList.add('is-visible');
    }

    function renderToolbar(block) {
        if (!block || state.readOnly) return toolbar.classList.remove('is-visible');
        const type = block.dataset.fbvType;
        const reusable = block.dataset.fbvReusable;
        const label = state.labels[type] || type;
        const index = blocks().indexOf(block);
        const button = (action, title, disabled = false) =>
            `<button type="button" data-action="${action}" title="${title}" ${disabled ? 'disabled' : ''}>${icons[action]}</button>`;

        toolbar.innerHTML =
            `<span class="fbv-toolbar__label">${reusable ? 'Reusable · ' : ''}${label}</span>` +
            button('edit', 'Edit in form') +
            (!reusable && state.fields[type]?.length ? button('settings', 'Block settings') : '') +
            (reusable ? button('external', 'Edit reusable block') : '') +
            button('up', 'Move up', index === 0) +
            button('down', 'Move down', index === blocks().length - 1) +
            button('duplicate', 'Duplicate') +
            button('hide', 'Hide') +
            button('remove', 'Delete') +
            button('add', 'Add block below');
        toolbar.dataset.id = block.dataset.fbvSet;

        const r = block.getBoundingClientRect();
        const top = Math.max(r.top + scrollY, scrollY + 8);
        Object.assign(toolbar.style, { top: `${top}px`, left: `${r.left + scrollX + 8}px` });
        toolbar.classList.add('is-visible');
    }

    function refresh() {
        place(hoverBox, state.hovered !== state.selected ? state.hovered : null);
        place(selectBox, state.selected);
        renderToolbar(state.hovered || state.selected);
    }

    function select(block, notify = true) {
        state.selected = block;
        refresh();
        if (block && notify) send('select', { id: block.dataset.fbvSet });
    }

    // ---- Set picker ------------------------------------------------------------------------
    function openPicker(after, anchor) {
        if (!state.sets.length) return;
        picker.innerHTML =
            `<input type="search" placeholder="Search blocks…" class="fbv-picker__search">` +
            state.sets
                .map(
                    (group) =>
                        `<div class="fbv-picker__group"><p>${group.display}</p>` +
                        group.sets
                            .map((s) => `<button type="button" data-type="${s.handle}"><strong>${s.display}</strong>${s.instructions ? `<span>${s.instructions}</span>` : ''}</button>`)
                            .join('') +
                        '</div>',
                )
                .join('');
        picker.dataset.after = after || '';
        const r = anchor.getBoundingClientRect();
        Object.assign(picker.style, { top: `${r.bottom + scrollY + 6}px`, left: `${Math.min(r.left + scrollX, scrollX + innerWidth - 340)}px` });
        picker.classList.add('is-visible');
        picker.querySelector('input').focus();
    }
    const closePicker = () => picker.classList.remove('is-visible');

    function openSettings(id, type, anchor) {
        picker.innerHTML =
            `<div class="fbv-picker__group"><p>${state.labels[type] || type} settings</p>` +
            state.fields[type].map((f) => `<button type="button" data-field="${f.handle}"><strong>${f.display}</strong></button>`).join('') +
            '</div>';
        picker.dataset.id = id;
        const r = anchor.getBoundingClientRect();
        Object.assign(picker.style, { top: `${r.bottom + scrollY + 6}px`, left: `${Math.min(r.left + scrollX, scrollX + innerWidth - 340)}px` });
        picker.classList.add('is-visible');
    }

    picker.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase();
        picker.querySelectorAll('button').forEach((b) => (b.hidden = q && !b.textContent.toLowerCase().includes(q)));
    });
    picker.addEventListener('click', (e) => {
        const f = e.target.closest('button[data-field]');
        if (f) {
            send('openField', { id: picker.dataset.id, path: f.dataset.field });
            return closePicker();
        }
        const b = e.target.closest('button[data-type]');
        if (!b) return;
        send('add', { after: picker.dataset.after || null, type: b.dataset.type });
        closePicker();
    });

    toolbar.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-action]');
        if (!b) return;
        e.stopPropagation();
        const id = toolbar.dataset.id;
        const block = blockById(id);
        switch (b.dataset.action) {
            case 'edit': return select(block);
            case 'external': return window.open(block.dataset.fbvReusable, '_blank');
            case 'up': case 'down': return send('move', { id, dir: b.dataset.action });
            case 'duplicate': return send('duplicate', { id });
            case 'hide': return send('hide', { id });
            case 'remove': return send('remove', { id });
            case 'add': return openPicker(id, b);
            case 'settings': return openSettings(id, block.dataset.fbvType, b);
        }
    });

    // ---- Empty state -----------------------------------------------------------------------
    function renderEmptyState() {
        const root = document.querySelector('[data-fbv-root]');
        if (!root || state.readOnly) return;
        const bar = el('div', 'fbv-add-bar', `<button type="button">${icons.add}<span>Add block</span></button>`);
        bar.querySelector('button').addEventListener('click', (e) => {
            e.stopPropagation();
            const last = blocks().at(-1);
            openPicker(last?.dataset.fbvSet, e.currentTarget);
        });
        state.addBar = bar;
        root.append(bar);
    }

    // ---- Inline text editing ---------------------------------------------------------------
    const editable = (node) => {
        const f = node?.closest?.('[data-fbv-field]');
        // Reusable blocks are editable when they carry their entry id: edits save to that entry.
        const reusable = f?.closest('[data-fbv-reusable]');
        return f && (!reusable || reusable.dataset.fbvEntry) && !state.readOnly ? f : null;
    };

    const normalize = (text, multiline) => {
        text = text.replace(/\u00a0/g, ' ');
        return multiline ? text.replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim() : text.replace(/\s+/g, ' ').trim();
    };

    function startEditing(field) {
        if (state.editing === field) return;
        const multiline = field.hasAttribute('data-fbv-multiline');
        // Swap the rendered markup for its plain text: template indentation and <br>s would
        // otherwise show up as extra lines/spaces once the element becomes editable.
        const text = normalize(field.innerText, multiline);
        field.dataset.fbvOriginalHtml = field.innerHTML;
        field.dataset.fbvOriginal = text;
        field.textContent = text;
        field.setAttribute('contenteditable', 'plaintext-only');
        field.classList.add('fbv-editing');
        state.editing = field;
        field.focus();
        const range = document.createRange();
        range.selectNodeContents(field);
        range.collapse(false);
        getSelection().removeAllRanges();
        getSelection().addRange(range);
    }

    function stopEditing(field, save = true) {
        // Read before dropping .fbv-editing: without its pre-wrap, innerText collapses line breaks.
        const value = normalize(field.innerText, field.hasAttribute('data-fbv-multiline'));
        field.removeAttribute('contenteditable');
        field.classList.remove('fbv-editing');
        state.editing = null;
        if (!save || value === field.dataset.fbvOriginal) return (field.innerHTML = field.dataset.fbvOriginalHtml);
        const block = field.closest('[data-fbv-set]');
        if (!block) return;
        const entry = block.dataset.fbvEntry;
        if (!entry) return send('editText', { id: block.dataset.fbvSet, path: field.dataset.fbvField, value });
        // Saved to the Blocks entry with the page; show the text now in every copy of that block on the page.
        document
            .querySelectorAll(`[data-fbv-entry="${CSS.escape(entry)}"] [data-fbv-field="${CSS.escape(field.dataset.fbvField)}"]`)
            .forEach((f) => (f.textContent = value));
        send('editReusable', { entry, path: field.dataset.fbvField, value });
    }

    document.addEventListener('keydown', (e) => {
        const f = state.editing;
        if (!f) return;
        if (e.key === 'Escape') { e.preventDefault(); stopEditing(f, false); f.blur(); }
        if (e.key === 'Enter' && !(e.shiftKey && f.hasAttribute('data-fbv-multiline'))) { e.preventDefault(); f.blur(); }
    });
    document.addEventListener('focusout', (e) => state.editing === e.target && stopEditing(e.target));

    // ---- Pointer ----------------------------------------------------------------------------
    document.addEventListener('mouseover', (e) => {
        const block = e.target.closest?.('[data-fbv-set]');
        if (block === state.hovered) return;
        state.hovered = block;
        if (!toolbar.contains(e.target)) refresh();
    });

    document.addEventListener(
        'click',
        (e) => {
            if (toolbar.contains(e.target) || picker.contains(e.target)) return;
            if (state.editing && !state.editing.contains(e.target)) stopEditing(state.editing);
            if (picker.classList.contains('is-visible')) closePicker();
            const block = e.target.closest('[data-fbv-set]');
            if (!block) return;
            // Inside the canvas, links and buttons shouldn't navigate away from the page being edited.
            if (e.target.closest('a, button, [type=submit]')) e.preventDefault();
            const field = editable(e.target);
            const opener = !field && !block.dataset.fbvReusable && e.target.closest('[data-fbv-open]');
            state.selected = block;
            refresh();
            const id = block.dataset.fbvSet;
            if (field) {
                // Show the field in the form too (a button label shows its whole button), without taking focus.
                const owner = field.parentElement.closest('a[data-fbv-open], button[data-fbv-open]');
                if (state.editing !== field && !block.dataset.fbvReusable) send('openField', { id, path: owner?.dataset.fbvOpen || field.dataset.fbvField, focus: false });
                startEditing(field);
            } else if (opener) send('openField', { id, path: opener.dataset.fbvOpen });
            else send('select', { id });
        },
        true,
    );

    document.addEventListener('keydown', (e) => e.key === 'Escape' && closePicker());
    addEventListener('scroll', refresh, { passive: true });
    addEventListener('resize', refresh);

    // ---- Update without reloading ------------------------------------------------------------
    // With `refresh: false` on the preview target, Statamic fetches the re-rendered page and
    // hands it to this hook instead of reloading the iframe. Only blocks whose server markup
    // changed are replaced, so scroll position, playing videos and untouched blocks stay as-is.
    const source = new WeakMap(); // element -> server HTML it was rendered from
    const remember = (root) => root.querySelectorAll(':scope > *').forEach((n) => source.set(n, n.outerHTML));
    const pageRoot = (doc) => doc.querySelector('[data-fbv-root]');
    if (pageRoot(document)) remember(pageRoot(document));

    // Everything on the page except the blocks and this overlay, as rendered by the server.
    // Only what precedes the overlay script counts: this runs before the browser has parsed
    // anything after it (trailing whitespace, markup other middleware appends).
    const outside = (doc) => {
        const body = doc.body.cloneNode(true);
        body.querySelector('[data-fbv-root]')?.replaceChildren();
        const script = body.querySelector('script[data-fbv-overlay]');
        while (script?.nextSibling) script.nextSibling.remove();
        body.querySelectorAll('[data-fbv-overlay], [class^="fbv-"]').forEach((n) => n.remove());
        return body.innerHTML;
    };
    const outsideSource = outside(document);

    window.StatamicLivePreviewMorph = (doc, next) => {
        const root = pageRoot(doc);
        const nextRoot = pageRoot(next);
        // Something outside the blocks changed (a header partial, navigation...): reload to show it.
        if (!root || !nextRoot || outside(next) !== outsideSource) return location.reload();
        if (state.editing) stopEditing(state.editing, false);

        const current = [...root.children].filter((n) => n !== state.addBar);
        const byKey = new Map(current.map((n, i) => [n.dataset.fbvSet || `#${i}`, n]));
        const children = [...nextRoot.children].map((n, i) => {
            const html = n.outerHTML;
            const old = byKey.get(n.dataset.fbvSet || `#${i}`);
            if (old && source.get(old) === html) return old;
            const fresh = doc.importNode(n, true);
            source.set(fresh, html);
            return fresh;
        });
        root.replaceChildren(...children, ...(state.addBar ? [state.addBar] : []));

        doc.title = next.title;

        const find = (b) => b && (b.isConnected ? b : blockById(b.dataset.fbvSet));
        state.selected = find(state.selected);
        state.hovered = find(state.hovered);
        refresh();
        doc.dispatchEvent(new CustomEvent('fbv:updated'));
    };

    // ---- Messages from the CP --------------------------------------------------------------
    addEventListener('message', (e) => {
        const d = e.data;
        if (e.source !== window.parent || !d || d.source !== 'fbv-cp') return;
        if (d.action === 'init') {
            state.sets = d.sets || [];
            state.readOnly = !!d.readOnly;
            state.labels = Object.fromEntries(state.sets.flatMap((g) => g.sets.map((s) => [s.handle, s.display])));
            state.labels.block = state.labels.block || 'Block';
            state.fields = Object.fromEntries(state.sets.flatMap((g) => g.sets.map((s) => [s.handle, s.fields || []])));
            document.documentElement.classList.add('fbv-ready');
            renderEmptyState();
            if (d.selected) select(blockById(d.selected), false);
        }
        if (d.action === 'highlight') {
            const block = blockById(d.id);
            if (!block) return;
            select(block, false);
            block.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    send('ready');
})();
