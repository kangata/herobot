<template>
    <AppLayout :title="integration.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ integration.name }}</h1>
                    <div class="mt-2">
                        <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded">Whatsapp</div>
                    </div>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <PrimaryButton :href="route('integrations.edit', integration.id)">
                        <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Edit integration
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <hr class="mb-4" />

        <!-- TODO -->
    </AppLayout>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { PencilIcon, PlusIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    integration: {
        type: Object,
        required: true,
    },
});
</script>
