<template>
    <form @submit.prevent="handleSubmit" class="w-full max-w-2xl">
        <div class="space-y-12">
            <div class="mb-6">
                <h2 class="text-base font-semibold leading-7 text-gray-900">{{ title }}</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">{{ subtitle }}</p>
            </div>
        </div>

        <div class="mb-6">
            <InputLabel for="name" value="Tool Name" />
            <TextInput
                id="name"
                v-model="form.name"
                type="text"
                placeholder="e.g., Get Weather, Send Email"
                required
                autofocus
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div class="mb-6">
            <InputLabel for="description" value="Description" />
            <TextInput
                id="description"
                v-model="form.description"
                type="text"
                placeholder="Describe what this tool does and how it helps users"
                required
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="mb-6">
            <InputLabel for="type" value="Tool Type" />
            <select
                id="type"
                v-model="form.type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                required
                @change="handleTypeChange"
            >
                <option value="">Select a tool type</option>
                <option value="http">HTTP API</option>
            </select>
            <InputError class="mt-2" :message="form.errors.type" />
            <p class="mt-2 text-sm text-gray-500">Choose the type of tool you want to create</p>
        </div>

        <div class="mb-6" v-if="form.type">
            <InputLabel value="API Configuration" />
            <div class="mt-2">
                <HttpToolConfig 
                    v-model="toolConfig"
                    @update:modelValue="updateToolParams"
                />
            </div>
            <p class="mt-2 text-sm text-gray-500">Configure the HTTP API endpoint and parameters.</p>
        </div>

        <div class="mb-6" v-if="form.type">
            <InputLabel value="Parameters Schema" />
            <div class="mt-2">
                <ParametersSchemaBuilder 
                    v-model="parametersSchema"
                    @update:modelValue="updateParametersSchema"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.parameters_schema" />
            <p class="mt-2 text-sm text-gray-500">Define what parameters users can provide when using this tool.</p>
        </div>

        <div class="mb-6">
            <div class="flex items-center">
                <Checkbox
                    id="is_active"
                    v-model:checked="form.is_active"
                />
                <InputLabel for="is_active" value="Active" class="ml-2" />
            </div>
            <p class="mt-2 text-sm text-gray-500">Tool will be available for use when active</p>
        </div>

        <div class="flex flex-row text-right">
            <SecondaryButton class="mr-2" :href="cancelRoute">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="mr-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ submitText }}
            </PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import HttpToolConfig from './HttpToolConfig.vue';
import ParametersSchemaBuilder from './ParametersSchemaBuilder.vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        required: true
    },
    tool: {
        type: Object,
        default: null
    },
    submitText: {
        type: String,
        default: 'Submit'
    },
    cancelRoute: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['submit']);

const toolConfig = ref({});
const parametersSchema = ref({});

// Initialize form with existing data or defaults
const initializeFormData = () => {
    if (props.tool) {
        return {
            name: props.tool.name,
            description: props.tool.description,
            type: props.tool.type,
            params: JSON.stringify(props.tool.params, null, 2),
            parameters_schema: JSON.stringify(props.tool.parameters_schema, null, 2),
            is_active: props.tool.is_active,
        };
    } else {
        return {
            name: '',
            description: '',
            type: '',
            params: '{}',
            parameters_schema: '{}',
            is_active: true,
        };
    }
};

const form = useForm(initializeFormData());

// Initialize the GUI components with existing data (for edit mode)
onMounted(() => {
    if (props.tool && props.tool.params) {
        toolConfig.value = { ...props.tool.params };
        
        // Set body type and auth type based on existing config
        if (props.tool.params.body) {
            toolConfig.value.bodyType = typeof props.tool.params.body === 'string' ? 'raw' : 'json';
        }
        
        if (props.tool.params.auth) {
            if (props.tool.params.auth.token) {
                toolConfig.value.authType = 'bearer';
            } else if (props.tool.params.auth.key_name) {
                toolConfig.value.authType = 'api_key';
            } else if (props.tool.params.auth.username) {
                toolConfig.value.authType = 'basic';
            }
        }
    }
    
    if (props.tool && props.tool.parameters_schema) {
        parametersSchema.value = { ...props.tool.parameters_schema };
    }
});

const handleTypeChange = () => {
    // Reset configurations when type changes
    toolConfig.value = {};
    parametersSchema.value = {};
    updateToolParams({});
    updateParametersSchema({});
};

const updateToolParams = (config) => {
    // Convert the GUI config to the format expected by the backend
    const params = { ...config };
    
    // Clean up the config object for storage
    delete params.bodyType;
    delete params.authType;
    
    form.params = JSON.stringify(params);
};

const updateParametersSchema = (schema) => {
    form.parameters_schema = JSON.stringify(schema);
};

const handleSubmit = () => {
    emit('submit', form);
};
</script>
