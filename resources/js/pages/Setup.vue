<script setup>
import { Head, router } from '@statamic/cms/inertia';
import { Card, Heading, Header, Button, Checkbox, CheckboxGroup, Alert } from '@statamic/cms/ui';
import { computed, ref } from 'vue';

const props = defineProps({
    title: String,
    installed: Boolean,
    collections: Array,
    newBlocks: Array,
    builderTemplate: String,
});

const templateWarnings = computed(() =>
    props.collections
        .filter((c) => c.hasFineBuilder)
        .flatMap((c) => [
            c.template !== props.builderTemplate
                ? __('fine-builder::messages.collection_template', { collection: c.title, template: c.template })
                : null,
            c.entriesWithOwnTemplate
                ? __('fine-builder::messages.entries_with_own_template', {
                      collection: c.title,
                      count: c.entriesWithOwnTemplate,
                      template: props.builderTemplate,
                  })
                : null,
        ])
        .filter(Boolean),
);

const selectedBlocks = ref(props.newBlocks.map((b) => b.handle));

const selectedCollections = ref(props.collections.filter((c) => c.hasFineBuilder).map((c) => c.handle));

const submitting = ref({ install: false, collections: false, blocks: false });

function addBlocks() {
    submitting.value.blocks = true;
    router.post(cp_url('fine-builder/blocks'), { blocks: selectedBlocks.value }, {
        onFinish: () => (submitting.value.blocks = false),
    });
}

function install(force = false) {
    if (force && !confirm(__('fine-builder::messages.overwrite_warning'))) return;
    submitting.value.install = true;
    router.post(cp_url('fine-builder/install'), { force }, {
        onFinish: () => (submitting.value.install = false),
    });
}

function saveCollections() {
    submitting.value.collections = true;
    router.post(cp_url('fine-builder/collections'), { collections: selectedCollections.value }, {
        onFinish: () => (submitting.value.collections = false),
    });
}
</script>

<template>
    <Head :title="__('fine-builder::messages.title')" />

    <Header>
        <Heading :text="__('fine-builder::messages.title')" />
    </Header>

    <div class="space-y-4">
        <Card>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('fine-builder::messages.intro') }}</p>
        </Card>

        <Card>
            <Heading :level="3" :text="__('fine-builder::messages.files_heading')" />
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ __('fine-builder::messages.files_intro') }}</p>

            <Alert class="mt-3" :variant="installed ? 'success' : 'warning'">
                {{ installed ? __('fine-builder::messages.files_installed') : __('fine-builder::messages.files_missing') }}
            </Alert>

            <div class="mt-4 flex gap-2">
                <Button
                    variant="primary"
                    :text="installed ? __('fine-builder::messages.reinstall') : __('fine-builder::messages.install')"
                    :disabled="submitting.install"
                    @click="install(false)"
                />
                <Button
                    v-if="installed"
                    variant="danger"
                    :text="__('fine-builder::messages.overwrite')"
                    :disabled="submitting.install"
                    @click="install(true)"
                />
            </div>
        </Card>

        <Card v-if="newBlocks.length">
            <Heading :level="3" :text="__('fine-builder::messages.new_blocks_heading')" />
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ __('fine-builder::messages.new_blocks_intro') }}</p>

            <CheckboxGroup v-model="selectedBlocks" class="mt-3">
                <Checkbox
                    v-for="block in newBlocks"
                    :key="block.handle"
                    :value="block.handle"
                    :label="block.display"
                    :description="block.instructions"
                />
            </CheckboxGroup>

            <div class="mt-4">
                <Button
                    variant="primary"
                    :text="__('fine-builder::messages.add_blocks')"
                    :disabled="submitting.blocks || !selectedBlocks.length"
                    @click="addBlocks"
                />
            </div>
        </Card>

        <Card>
            <Heading :level="3" :text="__('fine-builder::messages.collections_heading')" />
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ __('fine-builder::messages.collections_intro') }}</p>

            <div class="mt-3 space-y-2">
                <p class="text-sm font-medium text-gray-600">{{ __('fine-builder::messages.select_collections') }}</p>
                <CheckboxGroup v-model="selectedCollections" inline>
                    <Checkbox
                        v-for="collection in collections"
                        :key="collection.handle"
                        :value="collection.handle"
                        :label="collection.title"
                    />
                </CheckboxGroup>
            </div>

            <div class="mt-4">
                <Button
                    variant="primary"
                    :text="__('fine-builder::messages.save_collections')"
                    :disabled="submitting.collections"
                    @click="saveCollections"
                />
            </div>

            <Heading :level="3" class="mt-6" :text="__('fine-builder::messages.template_heading')" />
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ __('fine-builder::messages.template_intro', { template: builderTemplate }) }}</p>
            <Alert v-for="warning in templateWarnings" :key="warning" class="mt-3" variant="warning">{{ warning }}</Alert>

            <Alert class="mt-3">{{ __('fine-builder::messages.build_notice') }}</Alert>
            <Alert class="mt-3">{{ __('fine-builder::messages.make_block_hint') }}</Alert>
        </Card>
    </div>
</template>
