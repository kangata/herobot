<template>
    <div class="space-y-6 mb-6">
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">AI Service Configuration</h3>
            <p class="text-sm text-gray-600 mb-6">Configure which AI providers and models to use for different services. Leave empty to use global defaults.</p>
            
            <!-- AI Services -->
            <div v-for="service in services" :key="service.id" class="mb-6">
                <InputLabel :for="service.id" :value="service.label" />
                <select
                    :id="service.id"
                    v-model="form[service.id]"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Use Global Default</option>
                    <optgroup v-for="providerGroup in getModelsForService(service.modelKey)" :key="providerGroup.provider" :label="providerGroup.label">
                        <option v-for="model in providerGroup.models" :key="model.value" :value="model.value">
                            {{ providerGroup.label }} - {{ model.label }}
                        </option>
                    </optgroup>
                </select>
                <InputError class="mt-2" :message="form.errors[service.id]" />
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
        <div v-if="showCustomApiKeys">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Custom API Keys</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Custom API Keys Benefits
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>When you provide custom API keys:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li><strong>No credits consumed</strong> - Requests use your personal API quota</li>
                                <li><strong>No transaction records</strong> - Usage won't appear in team billing</li>
                                <li><strong>Direct billing</strong> - Costs are charged directly to your API provider account</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-6">Optionally provide custom API keys for specific providers. Leave empty to use global configuration and consume team credits.</p>
            
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
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    aiModels: {
        type: Object,
        default: () => ({ providers: {}, services: {} })
    }
});

const showCustomApiKeys = ref(false);

// Service configurations mapped to service types
const services = computed(() => [
    {
        id: 'ai_chat_service',
        label: props.aiModels.services?.chat?.name || 'Chat Service',
        modelKey: 'chat'
    },
    {
        id: 'ai_embedding_service',
        label: props.aiModels.services?.embedding?.name || 'Embedding Service',
        modelKey: 'embedding'
    },
    {
        id: 'ai_speech_to_text_service',
        label: props.aiModels.services?.speech?.name || 'Speech-to-Text Service',
        modelKey: 'speech'
    }
]);

const getModelsForService = (serviceType) => {
    if (!props.aiModels?.providers) return [];
    
    return Object.entries(props.aiModels.providers).map(([providerId, provider]) => ({
        provider: providerId,
        label: provider.name || providerId,
        models: Object.entries(provider.models || {})
            .filter(([modelId, model]) => model?.services?.includes(serviceType))
            .map(([modelId, model]) => ({
                value: `${providerId}/${modelId}`,
                label: model.name || modelId
            }))
    })).filter(group => group.models.length > 0);
};
</script>
