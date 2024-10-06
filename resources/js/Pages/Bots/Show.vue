<template>
    <AppLayout :title="bot.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ bot.name }}</h1>
                    <p class="mt-2 text-sm text-gray-700">{{ bot.description }}</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <PrimaryButton :href="route('bots.edit', bot.id)">
                        <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Edit bot
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <hr class="mb-4" />

        <!-- Integration Status -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4">
            Integration Status
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div v-for="integration in bot.integrations" :key="integration.id" class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded mb-2 capitalize">
                        {{ integration.type }}
                    </div>
                    <div class="font-medium">{{ integration.name }}</div>
                    <div class="text-sm text-gray-500 mt-2">{{ $filters.formatPhoneNumber(integration.phone) || '-' }}</div>
                </div>
                <div class="px-6 py-3 flex items-center text-xs">
                    <div class="flex items-center grow">
                        <div class="w-3 h-3 rounded-full mr-2" :class="integration.is_connected ? 'bg-green-500' : 'bg-red-500'"></div>
                        <div :class="integration.is_connected ? 'text-green-500' : 'text-red-500'">
                            {{ integration.is_connected ? 'Connected' : 'Disconnected' }}
                        </div>
                    </div>
                    <button 
                        @click="disconnectIntegration(integration.id)" 
                        class="px-4 py-2 bg-red-500 text-white rounded-md disabled:opacity-50"
                        :disabled="integration.isLoading"
                    >
                        {{ integration.isLoading ? 'Disconnecting...' : 'Disconnect' }}
                    </button>
                </div>
            </div>
            <div @click="openIntegrationModal" class="rounded-xl border border-gray-200 flex items-center justify-center cursor-pointer h-44">
                <PlusIcon class="h-6 w-6 text-gray-400" />
                <span class="ml-2">Connect new Integration</span>
            </div>
        </div>

        <!-- Knowledge Collection -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4 mt-6">
            Knowledge Collection
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div v-for="knowledge in bot.knowledge" :key="knowledge.id" class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="font-medium">{{ knowledge.name }}</div>
                    <div class="text-sm text-gray-500 mt-2 capitalize">{{ knowledge.type }}</div>
                </div>
                <div class="px-6 py-3 flex text-xs">
                    <button 
                        @click="disconnectKnowledge(knowledge.id)" 
                        class="grow px-4 py-2 bg-red-500 text-white rounded-md disabled:opacity-50"
                        :disabled="knowledge.isLoading"
                    >
                        {{ knowledge.isLoading ? 'Disconnecting...' : 'Disconnect' }}
                    </button>
                </div>
            </div>
            <div @click="openKnowledgeModal" class="rounded-xl border border-gray-200 flex items-center justify-center cursor-pointer h-44">
                <PlusIcon class="h-6 w-6 text-gray-400" />
                <span class="ml-2">Connect new Knowledge</span>
            </div>
        </div>

        <!-- Integration Modal -->
        <DialogModal :show="showIntegrationModal" @close="closeIntegrationModal">
            <template #title>
                Connect Integration
            </template>
            <template #content>
                <div v-for="integration in availableIntegrations" :key="integration.id" class="mb-2">
                    <button 
                        @click="connectIntegration(integration.id)" 
                        class="w-full text-left p-2 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="integration.isLoading"
                    >
                        {{ integration.isLoading ? 'Connecting...' : `${integration.name} (${$filters.formatPhoneNumber(integration.phone) || '-'})` }}
                    </button>
                </div>
                <div v-if="availableIntegrations.length === 0" class="text-center text-gray-500 py-4">
                    No available integrations to connect.
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeIntegrationModal" class="mr-2">
                    Cancel
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Knowledge Modal -->
        <DialogModal :show="showKnowledgeModal" @close="closeKnowledgeModal">
            <template #title>
                Connect Knowledge
            </template>
            <template #content>
                <div v-for="knowledge in availableKnowledge" :key="knowledge.id" class="mb-2">
                    <button 
                        @click="connectKnowledge(knowledge.id)" 
                        class="w-full text-left p-2 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="knowledge.isLoading"
                    >
                        {{ knowledge.isLoading ? 'Connecting...' : `${knowledge.name} (${knowledge.type})` }}
                    </button>
                </div>
                <div v-if="availableKnowledge.length === 0" class="text-center text-gray-500 py-4">
                    No available knowledge to connect.
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeKnowledgeModal" class="mr-2">
                    Cancel
                </SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import DialogModal from "@/Components/DialogModal.vue";
import { PencilIcon, PlusIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    bot: Object,
    availableIntegrations: Array,
    availableKnowledge: Array,
});

const showIntegrationModal = ref(false);
const showKnowledgeModal = ref(false);

const openIntegrationModal = () => showIntegrationModal.value = true;
const closeIntegrationModal = () => showIntegrationModal.value = false;
const openKnowledgeModal = () => showKnowledgeModal.value = true;
const closeKnowledgeModal = () => showKnowledgeModal.value = false;

const connectIntegration = (integrationId) => {
    const integration = props.availableIntegrations.find(i => i.id === integrationId);
    integration.isLoading = true;

    useForm({
        integration_id: integrationId
    }).post(route('bots.connect-integration', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeIntegrationModal();
            integration.isLoading = false;
        },
        onError: () => {
            integration.isLoading = false;
        }
    });
};

const disconnectIntegration = (integrationId) => {
    const integration = props.bot.integrations.find(i => i.id === integrationId);
    integration.isLoading = true;

    useForm({
        integration_id: integrationId
    }).delete(route('bots.disconnect-integration', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            integration.isLoading = false;
        },
        onError: () => {
            integration.isLoading = false;
        }
    });
};

const connectKnowledge = (knowledgeId) => {
    const knowledge = props.availableKnowledge.find(k => k.id === knowledgeId);
    knowledge.isLoading = true;

    useForm({
        knowledge_id: knowledgeId
    }).post(route('bots.connect-knowledge', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeKnowledgeModal();
            knowledge.isLoading = false;
        },
        onError: () => {
            knowledge.isLoading = false;
        }
    });
};

const disconnectKnowledge = (knowledgeId) => {
    const knowledge = props.bot.knowledge.find(k => k.id === knowledgeId);
    knowledge.isLoading = true;

    useForm({
        knowledge_id: knowledgeId
    }).delete(route('bots.disconnect-knowledge', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            knowledge.isLoading = false;
        },
        onError: () => {
            knowledge.isLoading = false;
        }
    });
};
</script>