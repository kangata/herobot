<template>
    <AppLayout title="Tools">
        <div>
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Tool Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Create, configure, and manage your AI tools.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none" v-if="tools.length > 0">
                    <PrimaryButton :href="route('tools.create')">
                        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Add tool
                    </PrimaryButton>
                </div>
            </div>

            <ToolList :tools="tools" />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { PlusIcon } from '@heroicons/vue/20/solid';
import ToolList from './Partials/ToolList.vue';

defineProps({
    tools: Array,
});
</script>
