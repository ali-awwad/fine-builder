import FineBuilderBridge from './fieldtypes/FineBuilderBridge.vue';
import Setup from './pages/Setup.vue';

Statamic.booting(() => {
    Statamic.$components.register('fine_builder_bridge-fieldtype', FineBuilderBridge);

    Statamic.$inertia.register('fine-builder::Setup', Setup);
});
