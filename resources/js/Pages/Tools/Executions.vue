<template>
    <AppLayout title="Tool Executions">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-6 sm:flex sm:items-center sm:justify-between">
                <div>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-4">
                            <li>
                                <Link :href="route('tools.index')" class="text-gray-400 hover:text-gray-500">
                                    <WrenchScrewdriverIcon class="flex-shrink-0 h-5 w-5" aria-hidden="true" />
                                    <span class="sr-only">Tools</span>
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
                                    <Link :href="route('tools.show', tool)" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                                        {{ tool.name }}
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
                                    <span class="ml-4 text-sm font-medium text-gray-500">Executions</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="mt-2 text-2xl font-semibold text-gray-900">Tool Executions</h1>
                    <p class="mt-2 text-sm text-gray-600">Execution history for "{{ tool.name }}"</p>
                </div>
            </div>

            <!-- Executions List -->
            <div v-if="executions.data.length === 0" class="text-center py-12">
                <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No executions</h3>
                <p class="mt-1 text-sm text-gray-500">This tool hasn't been executed yet.</p>
                <div class="mt-6">
                    <SecondaryButton :href="route('tools.show', tool)">
                        <ArrowLeftIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                        Back to Tool
                    </SecondaryButton>
                </div>
            </div>

            <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li v-for="execution in executions.data" :key="execution.id">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <StatusBadge :status="execution.status" />
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">
                                            Execution #{{ execution.id }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ new Date(execution.created_at).toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="text-sm text-gray-900">{{ execution.duration }}ms</p>
                                        <p class="text-sm text-gray-500">Duration</p>
                                    </div>
                                    <button @click="toggleDetails(execution.id)" 
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        {{ expandedExecutions.includes(execution.id) ? 'Hide' : 'Show' }} Details
                                    </button>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            <div v-if="expandedExecutions.includes(execution.id)" class="mt-4 border-t border-gray-200 pt-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Input Parameters -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Input Parameters</h4>
                                        <pre class="bg-gray-50 p-3 rounded-md text-sm overflow-x-auto">{{ JSON.stringify(execution.input_parameters, null, 2) }}</pre>
                                    </div>

                                    <!-- Output/Result -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                                            {{ execution.status === 'failed' ? 'Error' : 'Result' }}
                                        </h4>
                                        <pre class="bg-gray-50 p-3 rounded-md text-sm overflow-x-auto">{{ 
                                            execution.status === 'failed' 
                                                ? execution.error 
                                                : JSON.stringify(execution.output, null, 2) 
                                        }}</pre>
                                    </div>
                                </div>

                                <!-- Metadata -->
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Started At</dt>
                                            <dd class="text-sm text-gray-900">{{ new Date(execution.created_at).toLocaleString() }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Completed At</dt>
                                            <dd class="text-sm text-gray-900">{{ execution.completed_at ? new Date(execution.completed_at).toLocaleString() : 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Duration</dt>
                                            <dd class="text-sm text-gray-900">{{ execution.duration }}ms</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Status</dt>
                                            <dd class="text-sm text-gray-900">
                                                <StatusBadge :status="execution.status" />
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="executions.links" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="executions.prev_page_url" :href="executions.prev_page_url" 
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </Link>
                        <Link v-if="executions.next_page_url" :href="executions.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ executions.from }}</span> to <span class="font-medium">{{ executions.to }}</span> of <span class="font-medium">{{ executions.total }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="link in executions.links" :key="link.label">
                                    <Link v-if="link.url" :href="link.url"
                                        :class="[
                                            link.active 
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                        v-html="link.label">
                                    </Link>
                                    <span v-else
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300'
                                        ]"
                                        v-html="link.label">
                                    </span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { WrenchScrewdriverIcon, ClockIcon, ChevronRightIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    tool: Object,
    executions: Object,
});

const expandedExecutions = ref([]);

const toggleDetails = (executionId) => {
    const index = expandedExecutions.value.indexOf(executionId);
    if (index > -1) {
        expandedExecutions.value.splice(index, 1);
    } else {
        expandedExecutions.value.push(executionId);
    }
};
</script>
