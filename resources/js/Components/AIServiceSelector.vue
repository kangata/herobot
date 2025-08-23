<template>
    <div class="space-y-6 mb-6">
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">AI Service Configuration</h3>
            <p class="text-sm text-gray-600 mb-6">Configure which AI providers and models to use for different services. Leave empty to use global defaults.</p>
            
            <!-- Chat Service -->
            <div class="mb-6">
                <InputLabel for="ai_chat_service" value="Chat Service" />
                <select
                    id="ai_chat_service"
                    v-model="form.ai_chat_service"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Use Global Default</option>
                    <optgroup label="OpenAI">
                        <option value="openai/gpt-4o">OpenAI - GPT-4o</option>
                        <option value="openai/gpt-4o-mini">OpenAI - GPT-4o Mini</option>
                        <option value="openai/gpt-4-turbo">OpenAI - GPT-4 Turbo</option>
                        <option value="openai/gpt-3.5-turbo">OpenAI - GPT-3.5 Turbo</option>
                    </optgroup>
                    <optgroup label="Google Gemini">
                        <option value="gemini/gemini-2.5-flash">Google Gemini - Gemini 2.5 Flash</option>
                        <option value="gemini/gemini-1.5-pro">Google Gemini - Gemini 1.5 Pro</option>
                        <option value="gemini/gemini-1.5-flash">Google Gemini - Gemini 1.5 Flash</option>
                    </optgroup>
                </select>
                <InputError class="mt-2" :message="form.errors.ai_chat_service" />
            </div>

            <!-- Embedding Service -->
            <div class="mb-6">
                <InputLabel for="ai_embedding_service" value="Embedding Service" />
                <select
                    id="ai_embedding_service"
                    v-model="form.ai_embedding_service"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Use Global Default</option>
                    <optgroup label="OpenAI">
                        <option value="openai/text-embedding-3-large">OpenAI - Text Embedding 3 Large</option>
                        <option value="openai/text-embedding-3-small">OpenAI - Text Embedding 3 Small</option>
                        <option value="openai/text-embedding-ada-002">OpenAI - Text Embedding Ada 002</option>
                    </optgroup>
                    <optgroup label="Google Gemini">
                        <option value="gemini/text-embedding-004">Google Gemini - Text Embedding 004</option>
                    </optgroup>
                </select>
                <InputError class="mt-2" :message="form.errors.ai_embedding_service" />
            </div>

            <!-- Speech-to-Text Service -->
            <div class="mb-6">
                <InputLabel for="ai_speech_to_text_service" value="Speech-to-Text Service" />
                <select
                    id="ai_speech_to_text_service"
                    v-model="form.ai_speech_to_text_service"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Use Global Default</option>
                    <optgroup label="OpenAI">
                        <option value="openai/whisper-1">OpenAI - Whisper-1</option>
                    </optgroup>
                    <optgroup label="Google Gemini">
                        <option value="gemini/gemini-2.5-flash">Google Gemini - Gemini 2.5 Flash</option>
                        <option value="gemini/gemini-1.5-pro">Google Gemini - Gemini 1.5 Pro</option>
                        <option value="gemini/gemini-1.5-flash">Google Gemini - Gemini 1.5 Flash</option>
                    </optgroup>
                </select>
                <InputError class="mt-2" :message="form.errors.ai_speech_to_text_service" />
            </div>
        </div>

        <!-- Toggle for Custom API Keys -->
        <div class="border-t border-gray-200 pt-4">
            <button
                type="button"
                @click="showCustomApiKeys = !showCustomApiKeys"
                class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
                <svg class="w-4 h-4 mr-1" :class="{ 'rotate-90': showCustomApiKeys }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                {{ showCustomApiKeys ? 'Hide' : 'Show' }} Custom API Keys
            </button>
        </div>

        <!-- Custom API Keys Section -->
        <div class="border-t border-gray-200 pt-6" v-if="showCustomApiKeys">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Custom API Keys</h3>
            <p class="text-sm text-gray-600 mb-6">Optionally provide custom API keys for specific providers. Leave empty to use global configuration.</p>
            
            <!-- OpenAI API Key -->
            <div class="mb-6">
                <InputLabel for="openai_api_key" value="OpenAI API Key" />
                <TextInput
                    id="openai_api_key"
                    v-model="form.openai_api_key"
                    type="password"
                    placeholder="sk-..."
                    class="font-mono"
                />
                <InputError class="mt-2" :message="form.errors.openai_api_key" />
                <p class="mt-1 text-xs text-gray-500">Custom OpenAI API key for this bot (optional)</p>
            </div>

            <!-- Gemini API Key -->
            <div class="mb-6">
                <InputLabel for="gemini_api_key" value="Gemini API Key" />
                <TextInput
                    id="gemini_api_key"
                    v-model="form.gemini_api_key"
                    type="password"
                    placeholder="AI..."
                    class="font-mono"
                />
                <InputError class="mt-2" :message="form.errors.gemini_api_key" />
                <p class="mt-1 text-xs text-gray-500">Custom Gemini API key for this bot (optional)</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    }
});

const showCustomApiKeys = ref(false);
</script>
