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

        <hr />

        <div class="my-4 rounded-lg border border-gray-200 p-4 bg-gray-50">
            <p class="text-sm text-gray-700">{{ bot.prompt }}</p>
        </div>

        <hr class="mb-4" />

        <!-- Channel Status -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4">
            Channel Status
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div v-for="channel in bot.channels" :key="channel.id" class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded mb-2 capitalize">
                        {{ channel.type }}
                    </div>
                    <Link :href="route('channels.show', channel.id)" class="font-medium block">{{ channel.name }}</Link>
                    <div class="text-sm text-gray-500 mt-2">{{ $filters.formatPhoneNumber(channel.phone) || '-' }}</div>
                </div>
                <div class="px-6 py-3 flex items-center text-xs">
                    <div class="flex items-center grow">
                        <div class="w-3 h-3 rounded-full mr-2" :class="channel.is_connected ? 'bg-green-500' : 'bg-red-500'"></div>
                        <div :class="channel.is_connected ? 'text-green-500' : 'text-red-500'">
                            {{ channel.is_connected ? 'Connected' : 'Disconnected' }}
                        </div>
                    </div>
                    <button 
                        @click="disconnectChannel(channel.id)" 
                        class="px-4 py-2 bg-red-500 text-white rounded-md disabled:opacity-50"
                        :disabled="channel.isLoading"
                    >
                        {{ channel.isLoading ? 'Disconnecting...' : 'Disconnect' }}
                    </button>
                </div>
            </div>
            <div @click="openChannelModal" class="rounded-xl border border-gray-200 flex items-center justify-center cursor-pointer h-44">
                <PlusIcon class="h-6 w-6 text-gray-400" />
                <span class="ml-2">Connect Channel</span>
            </div>
        </div>

        <!-- Knowledge Collection -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4 mt-6">
            Knowledge Collection
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div v-for="knowledge in bot.knowledge" :key="knowledge.id" class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <Link :href="route('knowledges.edit', knowledge.id)" class="font-medium">{{ knowledge.name }}</Link>
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
                <span class="ml-2">Connect Knowledge</span>
            </div>
        </div>

        <!-- Channel Modal -->
        <DialogModal :show="showChannelModal" @close="closeChannelModal">
            <template #title>
                Connect Channel
            </template>
            <template #content>
                <div class="space-y-4">
                    <div v-if="availableChannels.length === 0" class="text-center text-gray-500 py-4">
                        No available channels to connect.
                    </div>
                    <div v-else v-for="channel in availableChannels" :key="channel.id" class="mb-2">
                        <button 
                            @click="connectChannel(channel.id)" 
                            class="w-full text-left p-2 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="channel.isLoading"
                        >
                            {{ channel.isLoading ? 'Connecting...' : `${channel.name} (${$filters.formatPhoneNumber(channel.phone) || '-'})` }}
                        </button>
                    </div>
                    <div class="border-t pt-4 mt-4">
                        <Link :href="route('channels.create', { bot_id: bot.id })" class="w-full">
                            <PrimaryButton class="w-full justify-center">
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Create New Channel
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeChannelModal" class="mr-2">
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
                <div class="space-y-4">
                    <div v-if="availableKnowledge.length === 0" class="text-center text-gray-500 py-4">
                        No available knowledge to connect.
                    </div>
                    <div v-else v-for="knowledge in availableKnowledge" :key="knowledge.id" class="mb-2">
                        <button 
                            @click="connectKnowledge(knowledge.id)" 
                            class="w-full text-left p-2 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="knowledge.isLoading"
                        >
                            {{ knowledge.isLoading ? 'Connecting...' : `${knowledge.name} (${knowledge.type})` }}
                        </button>
                    </div>
                    <div class="border-t pt-4 mt-4">
                        <Link :href="route('knowledges.create', { bot_id: bot.id })" class="w-full">
                            <PrimaryButton class="w-full justify-center">
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Create New Knowledge
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeKnowledgeModal" class="mr-2">
                    Cancel
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Floating Chat Widget -->
        <FloatingChatWidget :bot="bot" />
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import DialogModal from "@/Components/DialogModal.vue";
import FloatingChatWidget from "@/Components/FloatingChatWidget.vue";
import { PencilIcon, PlusIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    bot: Object,
    availableChannels: Array,
    availableKnowledge: Array,
});

const showChannelModal = ref(false);
const showKnowledgeModal = ref(false);

const openChannelModal = () => showChannelModal.value = true;
const closeChannelModal = () => showChannelModal.value = false;
const openKnowledgeModal = () => showKnowledgeModal.value = true;
const closeKnowledgeModal = () => showKnowledgeModal.value = false;

const connectChannel = (channelId) => {
    const channel = props.availableChannels.find(i => i.id === channelId);
    channel.isLoading = true;

    useForm({
        channel_id: channelId
    }).post(route('bots.connect-channel', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeChannelModal();
            channel.isLoading = false;
        },
        onError: () => {
            channel.isLoading = false;
        }
    });
};

const disconnectChannel = (channelId) => {
    const channel = props.bot.channels.find(i => i.id === channelId);
    channel.isLoading = true;

    useForm({
        channel_id: channelId
    }).delete(route('bots.disconnect-channel', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            channel.isLoading = false;
        },
        onError: () => {
            channel.isLoading = false;
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