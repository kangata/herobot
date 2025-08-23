<template>
    <AppLayout title="Usage">
        <div class="bg-white overflow-hidden sm:rounded-lg">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Usage Summary</h1>
                    <p class="mt-2 text-sm text-gray-700">Overview of your AI usage and token consumption.</p>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="mb-8">
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-indigo-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Usage Statistics</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                            <!-- Total Credits Used -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-200 rounded-md p-2">
                                        <CreditCardIcon class="h-6 w-6 text-green-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">Total Credits Used</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ formatNumber(summary.total_credits) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Tokens -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-200 rounded-md p-2">
                                        <ArrowUpIcon class="h-6 w-6 text-blue-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">Input Tokens</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ formatNumber(summary.total_input_tokens) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Output Tokens -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-purple-200 rounded-md p-2">
                                        <ArrowDownIcon class="h-6 w-6 text-purple-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">Output Tokens</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ formatNumber(summary.total_output_tokens) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Tokens -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-orange-200 rounded-md p-2">
                                        <ChartBarIcon class="h-6 w-6 text-orange-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">Total Tokens</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ formatNumber(summary.total_tokens) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Breakdown Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Usage by Provider -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-green-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Usage by Provider</h2>
                        </div>
                    </div>
                    <div class="px-6">
                        <div class="space-y-4">
                            <div v-if="usage_by_provider && usage_by_provider.length > 0" class="divide-y divide-gray-200">
                                <div v-for="provider in usage_by_provider" :key="provider.provider" class="flex items-center justify-between py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-4 h-4 rounded-full" :class="getProviderColor(provider.provider)"></div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 capitalize">{{ provider.provider }}</p>
                                            <p class="text-xs text-gray-500">{{ formatNumber(provider.total_input_tokens + provider.total_output_tokens) }} tokens</p>
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatNumber(provider.total_credits) }} credits
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <div class="text-gray-400 mb-2">
                                    <ChartBarIcon class="h-12 w-12 mx-auto" />
                                </div>
                                <p class="text-xs text-gray-500">No provider usage data available</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Models by Cost -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-blue-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Top Models by Cost</h2>
                        </div>
                    </div>
                    <div class="px-6">
                        <div class="space-y-4">
                            <div v-if="usage_by_model && usage_by_model.length > 0" class="divide-y divide-gray-200">
                                <div v-for="model in usage_by_model.slice(0, 5)" :key="`${model.provider}-${model.model}`" class="flex items-center justify-between py-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ model.model }}</p>
                                        <p class="text-xs text-gray-500">{{ model.provider }} • {{ model.usage_count }} requests</p>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatNumber(model.total_credits) }} credits
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <div class="text-gray-400 mb-2">
                                    <CreditCardIcon class="h-12 w-12 mx-auto" />
                                </div>
                                <p class="text-xs text-gray-500">No model usage data available</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage by Bot -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-purple-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Usage by Bot</h2>
                        </div>
                    </div>
                    <div class="px-6">
                        <div class="space-y-4">
                            <div v-if="usage_by_bot && usage_by_bot.length > 0" class="divide-y divide-gray-200">
                                <div v-for="bot in usage_by_bot.slice(0, 5)" :key="bot.bot_id" class="flex items-center justify-between py-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ bot.bot?.name || 'Unknown Bot' }}</p>
                                        <p class="text-xs text-gray-500">{{ bot.usage_count }} requests • {{ formatNumber(bot.total_input_tokens + bot.total_output_tokens) }} tokens</p>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatNumber(bot.total_credits) }} credits
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <div class="text-gray-400 mb-2">
                                    <ChartBarIcon class="h-12 w-12 mx-auto" />
                                </div>
                                <p class="text-xs text-gray-500">No bot usage data available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Usage History Section -->
            <div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-orange-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Usage History</h2>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider / Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bot</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens (In → Out)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="usage in usages.data" :key="usage.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(usage.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full mr-2" :class="getProviderColor(usage.provider)"></div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 capitalize">{{ usage.provider }}</div>
                                                <div class="text-sm text-gray-500">{{ usage.model }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ usage.bot?.name || 'Unknown' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatNumber(usage.input_tokens) }}{{ usage.output_tokens > 0 ? ` → ${formatNumber(usage.output_tokens)}` : '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ usage.tokens_per_second > 0 ? formatTPS(usage.tokens_per_second) : 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ formatCredits(usage.credits / 1000000) }}</td>
                                </tr>
                                <tr v-if="usages.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 text-center">No usage data found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6" v-if="usages.links.length > 3">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="usages.prev_page_url" :href="usages.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </Link>
                            <Link v-if="usages.next_page_url" :href="usages.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs text-gray-700">
                                    Showing {{ usages.from }} to {{ usages.to }} of {{ usages.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <template v-for="(link, index) in usages.links" :key="index">
                                        <Link v-if="link.url" :href="link.url"
                                            :class="[
                                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === usages.links.length - 1 ? 'rounded-r-md' : '',
                                                'relative inline-flex items-center px-4 py-2 border text-xs font-medium'
                                            ]"
                                            v-html="link.label">
                                        </Link>
                                        <span v-else
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-xs font-medium text-gray-500',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === usages.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                            v-html="link.label">
                                        </span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { 
    CreditCardIcon, 
    ChartBarIcon,
    ArrowUpIcon,
    ArrowDownIcon
} from '@heroicons/vue/24/outline'

defineProps({
    usages: Object,
    summary: Object,
    usage_by_provider: Array,
    usage_by_model: Array,
    usage_by_bot: Array,
    daily_usage: Array,
})

const formatCredits = (amount) => {
    const significantDigits = amount < 0.01 ? -Math.floor(Math.log10(amount)) + 1 : 2;
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: Math.min(significantDigits, 6)
    }).format(amount) + ' credits'
}

const formatNumber = (number) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(number)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatTPS = (val) => {
    const n = Number(val)
    return Number.isFinite(n) ? n.toFixed(2) + ' TPS' : 'N/A'
}

const getProviderColor = (provider) => {
    const colors = {
        'OpenAI': 'bg-green-500',
        'Gemini': 'bg-blue-500',
        'unknown': 'bg-gray-500'
    }
    return colors[provider] || colors.unknown
}
</script>
