<template>
    <AppLayout title="Pricing">
        <div class="bg-white overflow-hidden sm:rounded-lg">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Token Pricing</h1>
                    <p class="mt-2 text-sm text-gray-700">Current pricing for AI models.</p>
                </div>
                
                <!-- Exchange Rate Info -->
                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CurrencyDollarIcon class="h-5 w-5 text-blue-600" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">Exchange Rate</p>
                            <p class="text-xs text-blue-600">1 USD = {{ formatNumber(exchange_rate.usd_to_credits) }} Credits</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Tables -->
            <div class="space-y-8">
                <div v-for="(models, provider) in pricing" :key="provider" class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3" :class="getProviderColor(provider)"></div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ provider }}</h2>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Input / 1M Tokens</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Output / 1M Tokens</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(modelPricing, modelName) in models" :key="modelName" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ modelName }}</div>
                                        <div v-if="getModelCategory(modelName)" class="text-xs text-gray-500">{{ getModelCategory(modelName) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">${{ formatUSD(modelPricing.input_usd) }}</div>
                                            <div class="text-xs text-gray-500">{{ formatCredits(modelPricing.input_credits) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="modelPricing.output_credits > 0" class="text-sm text-gray-900">
                                            <div class="font-medium">${{ formatUSD(modelPricing.output_usd) }}</div>
                                            <div class="text-xs text-gray-500">{{ formatCredits(modelPricing.output_credits) }}</div>
                                        </div>
                                        <div v-else class="text-sm text-gray-400">
                                            N/A
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pricing Notes -->
            <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Pricing Notes</h3>
                <ul class="text-xs text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
                        All prices are per 1 million tokens (1M tokens)
                    </li>
                    <li class="flex items-start">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
                        Input tokens are charged for prompt and context, output tokens for generated responses
                    </li>
                    <li class="flex items-start">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
                        Embedding models only charge for input tokens as they don't generate text output
                    </li>
                    <li class="flex items-start">
                        <span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
                        Credits are automatically deducted from your team balance when using AI models
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline'

defineProps({
    pricing: Object,
    exchange_rate: Object,
})

const formatCredits = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' credits'
}

const formatUSD = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4
    }).format(amount)
}

const formatNumber = (number) => {
    return new Intl.NumberFormat('id-ID').format(number)
}

const getProviderColor = (provider) => {
    const colors = {
        'OpenAI': 'bg-green-500',
        'Gemini': 'bg-blue-500',
        'unknown': 'bg-gray-500'
    }
    return colors[provider] || colors.unknown
}

const getModelCategory = (modelName) => {
    if (modelName.includes('embedding')) {
        return 'Embedding Model'
    } else if (modelName.includes('gpt-5')) {
        return 'GPT-5 Series'
    } else if (modelName.includes('gpt-4.1')) {
        return 'GPT-4.1 Series'
    } else if (modelName.includes('gpt-4o')) {
        return 'GPT-4o Series'
    } else if (modelName.includes('gemini-2.5')) {
        return 'Gemini 2.5 Series'
    }
    return null
}
</script>
