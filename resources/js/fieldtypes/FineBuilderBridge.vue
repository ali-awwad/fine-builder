<script setup>
/**
 * The fine_builder_bridge fieldtype lives inside the entry's publish form. It listens to
 * messages from the Live Preview iframe (resources/preview/overlay.js) and applies them to
 * the builder Replicator through the publish container, so the form stays the single
 * source of truth (validation, revisions, saving) and Live Preview refreshes on its own.
 */
import { unref, onMounted, onBeforeUnmount } from 'vue';
import { injectPublishContext } from '@statamic/cms/ui';

const props = defineProps(['value', 'meta', 'config', 'handle', 'readOnly']);

const uid = () =>
    (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2) + Date.now().toString(36))
        .replace(/-/g, '')
        .slice(0, 12);

const clone = (value) => JSON.parse(JSON.stringify(value ?? null));

/** Same approach as Statamic's Replicator duplicateValues: fresh ids for every nested set/row, meta re-keyed. */
function duplicateValues(values, meta) {
    const ids = {};
    const remapValues = (v) => {
        if (Array.isArray(v)) return v.map(remapValues);
        if (!v || typeof v !== 'object') return v;
        const out = Object.fromEntries(Object.entries(v).map(([k, x]) => [k, remapValues(x)]));
        if (out._id) out._id = ids[out._id] = uid();
        if (out.type === 'set' && out.attrs?.id) out.attrs.id = ids[out.attrs.id] = uid();
        return out;
    };
    const remapMeta = (m) =>
        Array.isArray(m)
            ? m.map((x) => (typeof x === 'string' ? ids[x] ?? x : remapMeta(x)))
            : !m || typeof m !== 'object'
              ? m
              : Object.fromEntries(Object.entries(m).map(([k, x]) => [ids[k] ?? k, remapMeta(x)]));
    const newValues = remapValues(values);
    return { values: newValues, meta: remapMeta(meta) };
}

function setPath(target, path, value) {
    const keys = path.split('.');
    let node = target;
    for (const key of keys.slice(0, -1)) {
        if (node == null) return false;
        node = node[key];
    }
    if (node == null) return false;
    node[keys.at(-1)] = value;
    return true;
}

const csrf = () =>
    window.Statamic?.$config?.get?.('csrfToken') || document.querySelector('meta[name="csrf-token"]')?.content;

const ctx = injectPublishContext();
const field = props.meta?.field || 'fine_builder';
let preview = null; // { win, origin } of the Live Preview iframe
let selectedId = null;

const sets = () => clone(unref(ctx.values)?.[field] || []);
const fieldMeta = () => unref(ctx.meta)?.[field] || {};
const commit = (value) => ctx.setFieldValue(field, value);
const indexOf = (list, id) => list.findIndex((s) => s._id === id);
const post = (action, data = {}) => preview?.win.postMessage(clone({ source: 'fbv-cp', action, ...data }), preview.origin);

/** Top-level set cards of the builder Replicator, in value order. */
function setElements() {
    const types = new Set((props.meta?.sets || []).flatMap((g) => g.sets.map((s) => s.handle)).concat('block'));
    return [...document.querySelectorAll('[data-replicator-set]')].filter(
        (el) => !el.parentElement.closest('[data-replicator-set]') && types.has(el.dataset.type),
    );
}

const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const flash = (el) =>
    el.animate([{ boxShadow: '0 0 0 3px rgb(99 102 241)' }, { boxShadow: '0 0 0 3px transparent' }], {
        duration: 1600,
        easing: 'ease-out',
    });
const expand = async (setEl) => {
    if (setEl?.dataset.collapsed !== 'true') return;
    setEl.querySelector(':scope > header button')?.click();
    await wait(150);
};

async function revealInForm(id, scroll = true) {
    const el = setElements()[indexOf(sets(), id)];
    if (!el) return null;
    await expand(el);
    if (scroll) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        flash(el);
    }
    return el;
}

/**
 * Scrolls to one field of a set, e.g. "image", "buttons.1" (a nested set) or "items.0.icon".
 * Fields are found by their label's `for`, which Statamic builds as field_<builder>_<index>_<path>.
 */
async function openField(id, path, focus = true) {
    const index = indexOf(sets(), id);
    const setEl = await revealInForm(id, false);
    if (!setEl) return;
    const keys = path.split('.');
    const domId = (parts) => ['field', field, index, ...parts].join('_');
    const group = (parts) => {
        const target = domId(parts);
        const node = document.querySelector(`label[for="${target}"]`) || document.getElementById(target);
        return node?.closest('[data-ui-input-group]');
    };
    let target = null;
    for (let n = 1; n < keys.length; n++) {
        if (!/^\d+$/.test(keys[n])) continue;
        // A numeric key inside a replicator: expand that nested set before looking deeper.
        const container = group(keys.slice(0, n));
        const nested = container && [...container.querySelectorAll('[data-replicator-set]')]
            .filter((el) => el.parentElement.closest('[data-replicator-set]') === setEl)[+keys[n]];
        if (nested) {
            await expand(nested);
            target = nested;
        }
    }
    target = group(keys) || target;
    // Grid rows etc. have no label of their own: fall back to the closest parent field.
    for (let n = keys.length - 1; !target && n > 0; n--) target = group(keys.slice(0, n));
    target ||= setEl;
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    flash(target);
    if (focus) target.querySelector('input, textarea, button:not([data-ui-dropdown-trigger])')?.focus({ preventScroll: true });
}

async function fetchSet(type) {
    const meta = fieldMeta();
    if (meta.new?.[type]) return { new: meta.new[type], defaults: meta.defaults?.[type] || {} };
    const response = await fetch(window.cp_url('fieldtypes/replicator/set'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf(),
        },
        // Statamic identifies the blueprint with a signed `token` since 6.3x, and with its
        // handle (`blueprint`, e.g. collections.pages.page) before. Each ignores the other key.
        body: JSON.stringify({
            token: unref(ctx.blueprint)?.token,
            blueprint: unref(ctx.blueprint)?.fqh,
            reference: unref(ctx.reference),
            field,
            set: type,
        }),
    });
    if (!response.ok) {
        const reason = await response.json().then((r) => r.message).catch(() => response.statusText);
        throw new Error(`Could not load set "${type}": ${reason}`);
    }
    return response.json();
}

function setRowMeta(id, rowMeta) {
    const meta = fieldMeta();
    ctx.setFieldMeta(field, { ...meta, existing: { ...(meta.existing || {}), [id]: clone(rowMeta) } });
}

const actions = {
    ready() {
        post('init', { sets: props.meta?.sets || [], readOnly: !!props.readOnly, selected: selectedId });
    },
    openField({ id, path, focus }) {
        selectedId = id;
        openField(id, path, focus !== false);
    },
    select({ id }) {
        selectedId = id;
        revealInForm(id);
    },
    move({ id, dir }) {
        const list = sets();
        const from = indexOf(list, id);
        const to = from + (dir === 'up' ? -1 : 1);
        if (from < 0 || to < 0 || to >= list.length) return;
        list.splice(to, 0, list.splice(from, 1)[0]);
        commit(list);
    },
    duplicate({ id }) {
        const list = sets();
        const i = indexOf(list, id);
        if (i < 0) return;
        const copy = duplicateValues(list[i], fieldMeta().existing?.[id]);
        setRowMeta(copy.values._id, copy.meta);
        list.splice(i + 1, 0, copy.values);
        commit(list);
    },
    hide({ id }) {
        const list = sets();
        const i = indexOf(list, id);
        if (i < 0) return;
        list[i].enabled = false;
        commit(list);
        window.Statamic?.$toast?.info?.(__('Block hidden. Switch it back on in the form to show it again.'));
    },
    remove({ id }) {
        const list = sets();
        const i = indexOf(list, id);
        if (i < 0 || !window.confirm(__('Delete this block?'))) return;
        list.splice(i, 1);
        commit(list);
    },
    async add({ after, type }) {
        try {
            const set = await fetchSet(type);
            const row = { ...clone(set.defaults), _id: uid(), type, enabled: true };
            setRowMeta(row._id, set.new);
            const list = sets();
            const i = after ? indexOf(list, after) + 1 : 0;
            list.splice(i, 0, row);
            commit(list);
            selectedId = row._id;
            setTimeout(() => revealInForm(row._id), 300);
        } catch (e) {
            window.Statamic?.$toast?.error?.(e.message);
        }
    },
    /** Reusable blocks live in their own entry: the edit waits in this field until the page is saved. */
    editReusable({ entry, path, value }) {
        const current = unref(ctx.values)?.[props.handle];
        const pending = current && !Array.isArray(current) && typeof current === 'object' ? clone(current) : {};
        pending[entry] = { ...(pending[entry] || {}), [path]: value };
        ctx.setFieldValue(props.handle, pending);
    },
    editText({ id, path, value }) {
        const list = sets();
        const i = indexOf(list, id);
        if (i < 0 || !setPath(list[i], path, value)) return;
        commit(list);
    },
};

function onMessage(event) {
    const data = event.data;
    if (!data || data.source !== 'fbv-preview') return;
    if (props.readOnly && !['ready', 'select', 'openField'].includes(data.action)) return;
    const iframe = document.getElementById('live-preview-iframe');
    if (!iframe || event.source !== iframe.contentWindow) return;
    preview = { win: event.source, origin: event.origin };
    actions[data.action]?.(data);
}

/** Clicking a set in the form highlights it in the preview. */
function onFormClick(event) {
    let el = event.target.closest?.('[data-replicator-set]');
    if (!el || !preview) return;
    while (el.parentElement.closest('[data-replicator-set]')) el = el.parentElement.closest('[data-replicator-set]');
    const id = sets()[setElements().indexOf(el)]?._id;
    if (id && id !== selectedId) post('highlight', { id: (selectedId = id) });
}

onMounted(() => {
    window.addEventListener('message', onMessage);
    document.addEventListener('click', onFormClick, true);
});
onBeforeUnmount(() => {
    window.removeEventListener('message', onMessage);
    document.removeEventListener('click', onFormClick, true);
});
</script>

<template>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <p>{{ __('Open Live Preview to edit the page visually:') }}</p>
        <!-- Inline list styles: the CP's Tailwind build doesn't ship list utilities. -->
        <ul style="list-style: disc; padding-inline-start: 1.25rem; margin-top: 0.25rem; line-height: 1.6">
            <li>{{ __('Click a block to open it here') }}</li>
            <li>{{ __('Click text to edit it in place') }}</li>
            <li>{{ __('Text edits in reusable blocks are saved to those blocks when you save this page') }}</li>
            <li>{{ __('Use the block toolbar to move, duplicate, hide, delete or add blocks') }}</li>
        </ul>
    </div>
</template>
