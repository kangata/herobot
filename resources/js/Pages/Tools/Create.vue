<template>
    <AppLayout title="Create Tool">
        <ToolForm
            title="Create a new tool"
            subtitle="Configure a new AI tool for your chatbots."
            submit-text="Create"
            :cancel-route="route('tools.index')"
            @submit="handleSubmit"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolForm from './Partials/ToolForm.vue';

const handleSubmit = (form) => {
    form.post(route('tools.store'));
};
</script>
