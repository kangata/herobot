<template>
    <AppLayout :title="tool.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ tool.name }}</h1>
                    <p class="mt-2 text-sm text-gray-700">{{ tool.description }}</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <PrimaryButton :href="route('tools.edit', tool)">
                        <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Edit tool
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <hr />

        <div class="my-4 rounded-lg border border-gray-200 p-4 bg-gray-50">
            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-gray-800 capitalize">
                            {{ tool.type }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <StatusBadge :status="tool.is_active ? 'active' : 'inactive'" />
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(tool.created_at).toLocaleDateString() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(tool.updated_at).toLocaleDateString() }}</dd>
                </div>
            </div>
        </div>

        <hr class="mb-4" />

        <!-- Configuration -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4">
            Configuration
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="bg-blue-500 text-white inline-block py-1 px-2 text-xs rounded mb-2">
                        Parameters
                    </div>
                    <div class="font-medium">Tool Parameters</div>
                    <div class="text-sm text-gray-500 mt-2">API configuration and settings</div>
                </div>
                <div class="px-6 py-3">
                    <pre class="bg-gray-50 p-3 rounded-md text-xs overflow-x-auto">{{ JSON.stringify(tool.params, null, 2) }}</pre>
                </div>
            </div>
            
            <div class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded mb-2">
                        Schema
                    </div>
                    <div class="font-medium">Parameters Schema</div>
                    <div class="text-sm text-gray-500 mt-2">Input validation rules</div>
                </div>
                <div class="px-6 py-3">
                    <pre class="bg-gray-50 p-3 rounded-md text-xs overflow-x-auto">{{ JSON.stringify(tool.parameters_schema, null, 2) }}</pre>
                </div>
            </div>
        </div>

        <!-- Recent Executions -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4 mt-6">
            Recent Executions
        </h3>

        <div class="grid grid-cols-1 gap-4">
            <div v-if="tool.executions && tool.executions.length > 0" class="space-y-3">
                <div v-for="execution in tool.executions.slice(0, 5)" :key="execution.id" 
                     class="rounded-xl border border-gray-200 text-base">
                    <div class="p-6 border-b border-gray-900/5">
                        <div :class="execution.status === 'completed' ? 'bg-green-500' : execution.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500'" 
                             class="text-white inline-block py-1 px-2 text-xs rounded mb-2 capitalize">
                            {{ execution.status }}
                        </div>
                        <div class="font-medium">{{ new Date(execution.created_at).toLocaleString() }}</div>
                        <div class="text-sm text-gray-500 mt-2">Duration: {{ execution.duration }}ms</div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <Link :href="route('tools.executions', tool)" class="text-sm text-indigo-600 hover:text-indigo-500">
                        View all executions
                    </Link>
                </div>
            </div>
            <div v-else class="rounded-xl border border-gray-200 flex items-center justify-center h-44">
                <div class="text-center">
                    <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No executions yet</h3>
                    <p class="mt-1 text-sm text-gray-500">This tool hasn't been executed yet.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fixed bottom-6 right-6 space-y-2">
            <button @click="showTestModal = true" 
                    class="flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700">
                <PlayIcon class="h-5 w-5 mr-2" />
                Test Tool
            </button>
        </div>

        <!-- Test Tool Modal -->
        <DialogModal :show="showTestModal" @close="showTestModal = false">
            <template #title>
                Test Tool: {{ tool.name }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Enter test parameters for this tool. Fill in the form fields below based on the tool's parameter schema.
                    </p>
                    
                    <div v-if="tool.parameters_schema && tool.parameters_schema.properties">
                        <DynamicParameterForm
                            :schema="tool.parameters_schema"
                            v-model="testFormData"
                            :errors="testForm.errors"
                        />
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <div class="text-gray-400">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Parameter Schema</h3>
                        <p class="mt-1 text-sm text-gray-500">This tool doesn't have a defined parameter schema.</p>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showTestModal = false">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="ml-3" @click="testTool" :class="{ 'opacity-25': testForm.processing }" :disabled="testForm.processing">
                    Test Tool
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Tool Response Modal -->
        <DialogModal :show="showResponseModal" @close="showResponseModal = false">
            <template #title>
                Tool Execution Result
            </template>

            <template #content>
                <div v-if="executionResponse" class="space-y-6">
                    <!-- Execution Status -->
                    <div class="flex items-center space-x-3">
                        <StatusBadge :status="executionResponse.status" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                Execution #{{ executionResponse.id }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Duration: {{ executionResponse.duration }}ms
                            </p>
                        </div>
                    </div>

                    <!-- Input Parameters -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Input Parameters</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ JSON.stringify(executionResponse.input_parameters, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Response/Result -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            {{ executionResponse.status === 'failed' ? 'Error Response' : 'Response' }}
                        </h4>
                        <div :class="executionResponse.status === 'failed' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'" 
                             class="rounded-lg p-4 border">
                            <pre class="text-sm whitespace-pre-wrap" 
                                 :class="executionResponse.status === 'failed' ? 'text-red-800' : 'text-green-800'">{{
                                executionResponse.status === 'failed' 
                                    ? executionResponse.error_message 
                                    : JSON.stringify(executionResponse.result, null, 2)
                            }}</pre>
                        </div>
                    </div>

                    <!-- Execution Metadata -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Execution Details</h4>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="font-medium text-gray-500">Started At</dt>
                                <dd class="text-gray-900">{{ new Date(executionResponse.created_at).toLocaleString() }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Completed At</dt>
                                <dd class="text-gray-900">{{ executionResponse.completed_at ? new Date(executionResponse.completed_at).toLocaleString() : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showResponseModal = false">
                    Close
                </SecondaryButton>
                
                <PrimaryButton class="ml-3" @click="showTestModal = true; showResponseModal = false">
                    Test Again
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmDelete" @close="confirmDelete = false">
            <template #title>
                Delete Tool
            </template>

            <template #content>
                Are you sure you want to delete "{{ tool.name }}"? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmDelete = false">
                    Cancel
                </SecondaryButton>

                <DangerButton class="ml-3" @click="deleteTool" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                    Delete Tool
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { PlayIcon, PencilIcon, TrashIcon, ClockIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DynamicParameterForm from '@/Components/DynamicParameterForm.vue';

const props = defineProps({
    tool: Object,
});

const showTestModal = ref(false);
const confirmDelete = ref(false);
const testFormData = ref({});
const showResponseModal = ref(false);
const executionResponse = ref(null);

const testForm = useForm({
    parameters: {},
});

const deleteForm = useForm({});

const testTool = () => {
    // Update the form parameters with the dynamic form data
    testForm.parameters = testFormData.value;
    
    testForm.post(route('tools.test', props.tool), {
        onSuccess: (response) => {
            showTestModal.value = false;
            testForm.reset();
            testFormData.value = {};
            
            // Show the response - check multiple possible response locations
            const testResult = response.props?.testResult || response.props?.flash?.testResult;
            const execution = response.props?.flash?.execution || 
                            response.props?.execution || 
                            response.props?.flash?.success?.execution ||
                            response.props?.data?.execution;
            
            if (testResult) {
                // Handle testResult format: {success: boolean, error?: string, data?: object}
                if (testResult.success === false) {
                    // Handle error response
                    executionResponse.value = {
                        id: 'test-' + Date.now(),
                        status: 'failed',
                        input_parameters: testForm.parameters,
                        error_message: testResult.error || 'Tool execution failed',
                        result: null,
                        duration: 0,
                        created_at: new Date().toISOString(),
                        completed_at: new Date().toISOString()
                    };
                } else {
                    // Handle success response
                    executionResponse.value = {
                        id: 'test-' + Date.now(),
                        status: 'completed',
                        input_parameters: testForm.parameters,
                        result: testResult.data || testResult.result || testResult,
                        duration: testResult.duration || 0,
                        created_at: new Date().toISOString(),
                        completed_at: new Date().toISOString()
                    };
                }
                showResponseModal.value = true;
            } else if (execution) {
                executionResponse.value = execution;
                showResponseModal.value = true;
            } else {
                // Fallback: Better error handling - check for any error messages
                const errorMessage = response.props?.flash?.error || 
                                   response.props?.flash?.message ||
                                   response.props?.errors?.message ||
                                   'No response data available';
                
                executionResponse.value = {
                    id: 'fallback-' + Date.now(),
                    status: response.props?.flash?.error ? 'failed' : 'completed',
                    input_parameters: testForm.parameters,
                    error_message: response.props?.flash?.error ? errorMessage : null,
                    result: response.props?.flash?.error ? null : errorMessage,
                    duration: 0,
                    created_at: new Date().toISOString(),
                    completed_at: new Date().toISOString()
                };
                showResponseModal.value = true;
            }
        },
        onError: (errors) => {
            // Handle validation errors - keep modal open to show errors
            console.error('Tool execution failed:', errors);
        }
    });
};

const deleteTool = () => {
    deleteForm.delete(route('tools.destroy', props.tool), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>
