<template>
    <AppLayout title="Bot Create">
        <form @submit.prevent="submit" class="w-full max-w-xl">
            <div class="space-y-12">
                <div class="mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Create a new bot</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">This information will be used to create your bot.</p>
                </div>
            </div>

            <div class="mb-6">
                <InputLabel for="name" value="Name" />
                <TextInput id="name" v-model="form.name" type="text" required autofocus />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            
            <div class="mb-6">
                <InputLabel for="description" value="Description" />
                <TextInput id="description" v-model="form.description" type="text" required />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <div class="mb-6">
                <InputLabel for="prompt" value="Bot Prompt" />
                <div class="mb-3 flex flex-wrap gap-2">
                    <button
                        v-for="(prompt, type) in predefinedPrompts"
                        :key="type"
                        type="button"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="updatePrompt(prompt)"
                    >
                        {{ formatPromptType(type) }}
                    </button>
                </div>
                <textarea
                    id="prompt"
                    v-model="form.prompt"
                    rows="6"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Enter your bot's prompt here..."
                    required
                ></textarea>
                <InputError class="mt-2" :message="form.errors.prompt" />
                <p class="mt-2 text-sm text-gray-500">Define how your bot should behave and interact with users. Click the buttons above to use predefined prompts, or write your own custom prompt.</p>
            </div>

            <div class="flex flex-row text-right">
                <SecondaryButton class="mr-2" :href="route('bots.index')">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="mr-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const predefinedPrompts = {
    customer_service: "You are a customer service AI assistant. Your goal is to help customers with their inquiries professionally and efficiently. Always maintain a friendly, helpful tone and prioritize customer satisfaction.",
    sales: "You are a sales AI assistant. Your role is to help potential customers learn about products/services, answer their questions, and guide them through the sales process. Be persuasive but honest, and focus on providing value.",
    technical_support: "You are a technical support AI assistant. Your purpose is to help users troubleshoot and resolve technical issues. Provide clear, step-by-step instructions and explain technical concepts in an understandable way.",
    personal_assistant: "You are a personal AI assistant. Your role is to help with task management, scheduling, reminders, and general inquiries. Be proactive, organized, and maintain a friendly, personal tone.",
};

const form = useForm({
    name: '',
    description: '',
    prompt: predefinedPrompts.customer_service,
});

const updatePrompt = (prompt) => {
    form.prompt = prompt;
};

const formatPromptType = (type) => {
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const submit = () => {
    form.post(route('bots.store'));
};
</script>
