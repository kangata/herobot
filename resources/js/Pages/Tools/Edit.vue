<template>
    <AppLayout title="Edit Tool">
        <ToolForm
            title="Edit tool"
            :subtitle="subtitle"
            submit-text="Update"
            :cancel-route="route('tools.show', tool)"
            :tool="tool"
            @submit="handleSubmit"
        />
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolForm from './Partials/ToolForm.vue';

const props = defineProps({
    tool: Object,
});

const subtitle = computed(() => `Update the configuration for "${props.tool.name}".`);

const handleSubmit = (form) => {
    form.put(route('tools.update', props.tool));
};
</script>
