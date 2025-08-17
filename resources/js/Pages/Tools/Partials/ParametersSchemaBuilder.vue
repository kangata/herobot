<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-medium text-gray-900">Tool Parameters</h4>
                <p class="text-sm text-gray-500">Define the parameters that users can provide when using this tool</p>
            </div>
            <button
                type="button"
                @click="addParameter"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
            >
                <PlusIcon class="-ml-0.5 mr-2 h-4 w-4" />
                Add Parameter
            </button>
        </div>

        <div v-if="parametersCount === 0" class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
            <DocumentIcon class="mx-auto h-8 w-8 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">No parameters defined yet</p>
            <p class="text-xs text-gray-400">Click "Add Parameter" to get started</p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(param, index) in parameters"
                :key="`param-${index}`"
                class="border border-gray-200 rounded-lg p-4 bg-gray-50"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Parameter Name -->
                        <div>
                            <InputLabel :for="`param-name-${index}`" value="Parameter Name" />
                            <TextInput
                                :id="`param-name-${index}`"
                                v-model="param.name"
                                placeholder="e.g., location, api_key"
                                class="mt-1 block w-full"
                                @input="updateSchema"
                            />
                        </div>

                        <!-- Parameter Type -->
                        <div>
                            <InputLabel :for="`param-type-${index}`" value="Type" />
                            <select
                                :id="`param-type-${index}`"
                                v-model="param.type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                @change="updateSchema"
                            >
                                <option value="string">String</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                                <option value="array">Array</option>
                                <option value="object">Object</option>
                            </select>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="removeParameter(index)"
                        class="ml-4 text-red-600 hover:text-red-500"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <InputLabel :for="`param-desc-${index}`" value="Description" />
                    <TextArea
                        :id="`param-desc-${index}`"
                        v-model="param.description"
                        placeholder="Describe what this parameter is used for"
                        class="mt-1 block w-full"
                        rows="2"
                        @input="updateSchema"
                    />
                </div>

                <!-- Additional Options -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Required -->
                    <div class="flex items-center">
                        <Checkbox
                            :id="`param-required-${index}`"
                            v-model:checked="param.required"
                            @change="updateSchema"
                        />
                        <InputLabel :for="`param-required-${index}`" value="Required" class="ml-2" />
                    </div>

                    <!-- Default Value -->
                    <div>
                        <InputLabel :for="`param-default-${index}`" value="Default Value" />
                        <TextInput
                            :id="`param-default-${index}`"
                            v-model="param.default"
                            placeholder="Optional default value"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>

                    <!-- Example Value -->
                    <div>
                        <InputLabel :for="`param-example-${index}`" value="Example" />
                        <TextInput
                            :id="`param-example-${index}`"
                            v-model="param.example"
                            placeholder="Example value"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>
                </div>

                <!-- String-specific options -->
                <div v-if="param.type === 'string'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel :for="`param-min-length-${index}`" value="Min Length" />
                        <TextInput
                            :id="`param-min-length-${index}`"
                            v-model.number="param.minLength"
                            type="number"
                            placeholder="Minimum length"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>
                    <div>
                        <InputLabel :for="`param-max-length-${index}`" value="Max Length" />
                        <TextInput
                            :id="`param-max-length-${index}`"
                            v-model.number="param.maxLength"
                            type="number"
                            placeholder="Maximum length"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>
                </div>

                <!-- Number-specific options -->
                <div v-if="param.type === 'number'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel :for="`param-min-${index}`" value="Minimum" />
                        <TextInput
                            :id="`param-min-${index}`"
                            v-model.number="param.minimum"
                            type="number"
                            placeholder="Minimum value"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>
                    <div>
                        <InputLabel :for="`param-max-${index}`" value="Maximum" />
                        <TextInput
                            :id="`param-max-${index}`"
                            v-model.number="param.maximum"
                            type="number"
                            placeholder="Maximum value"
                            class="mt-1 block w-full"
                            @input="updateSchema"
                        />
                    </div>
                </div>

                <!-- Enum options -->
                <div v-if="param.type === 'string'" class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <InputLabel value="Allowed Values (Optional)" />
                        <button
                            type="button"
                            @click="addEnumValue(index)"
                            class="text-sm text-indigo-600 hover:text-indigo-500"
                        >
                            + Add Value
                        </button>
                    </div>
                    <div v-if="param.enum && param.enum.length > 0" class="space-y-2">
                        <div
                            v-for="(enumValue, enumIndex) in param.enum"
                            :key="enumIndex"
                            class="flex items-center space-x-2"
                        >
                            <TextInput
                                v-model="param.enum[enumIndex]"
                                placeholder="Allowed value"
                                class="flex-1"
                                @input="updateSchema"
                            />
                            <button
                                type="button"
                                @click="removeEnumValue(index, enumIndex)"
                                class="text-red-600 hover:text-red-500"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Leave empty to allow any string value
                    </p>
                </div>
            </div>
        </div>

        <!-- Schema Preview -->
        <div v-if="parameters.length > 0" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h5 class="text-sm font-medium text-gray-900 mb-2">Generated Schema Preview</h5>
            <pre class="text-xs text-gray-600 overflow-x-auto">{{ JSON.stringify(generatedSchema, null, 2) }}</pre>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { PlusIcon, TrashIcon, DocumentIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const parameters = ref([]);
const parametersCount = computed(() => {
    console.log('parametersCount computed, length:', parameters.value.length);
    return parameters.value.length;
});

// Initialize parameters from existing schema
const initializeFromSchema = (schema) => {
    if (schema && schema.properties) {
        parameters.value = Object.entries(schema.properties).map(([name, prop]) => ({
            name,
            type: prop.type || 'string',
            description: prop.description || '',
            required: schema.required?.includes(name) || false,
            default: prop.default || '',
            example: prop.example || '',
            minLength: prop.minLength,
            maxLength: prop.maxLength,
            minimum: prop.minimum,
            maximum: prop.maximum,
            enum: prop.enum || []
        }));
    }
};

// Initialize if we have existing data
if (props.modelValue && Object.keys(props.modelValue).length > 0) {
    initializeFromSchema(props.modelValue);
}

// Watch for changes to modelValue (only on initial load, not during editing)
watch(() => props.modelValue, (newValue, oldValue) => {
    // Only initialize if we're going from empty to having data (initial load)
    if (newValue && Object.keys(newValue).length > 0 && parameters.value.length === 0) {
        initializeFromSchema(newValue);
    }
}, { deep: true });

const generatedSchema = computed(() => {
    const schema = {
        type: 'object',
        properties: {},
        required: []
    };

    parameters.value.forEach(param => {
        if (!param.name) return;

        const property = {
            type: param.type,
            description: param.description
        };

        // Add type-specific constraints
        if (param.type === 'string') {
            if (param.minLength) property.minLength = param.minLength;
            if (param.maxLength) property.maxLength = param.maxLength;
            if (param.enum && param.enum.length > 0 && param.enum.some(v => v.trim())) {
                property.enum = param.enum.filter(v => v.trim());
            }
        }

        if (param.type === 'number') {
            if (param.minimum !== undefined && param.minimum !== '') property.minimum = param.minimum;
            if (param.maximum !== undefined && param.maximum !== '') property.maximum = param.maximum;
        }

        if (param.default) property.default = param.default;
        if (param.example) property.example = param.example;

        schema.properties[param.name] = property;

        if (param.required) {
            schema.required.push(param.name);
        }
    });

    return schema;
});

const addParameter = () => {
    console.log('addParameter called, current length:', parameters.value.length);
    const newParam = {
        name: '',
        type: 'string',
        description: '',
        required: false,
        default: '',
        example: '',
        enum: []
    };
    parameters.value.push(newParam);
    console.log('After push, length:', parameters.value.length, 'array:', parameters.value);
    updateSchema();
};

const removeParameter = (index) => {
    parameters.value.splice(index, 1);
    updateSchema();
};

const addEnumValue = (paramIndex) => {
    if (!parameters.value[paramIndex].enum) {
        parameters.value[paramIndex].enum = [];
    }
    parameters.value[paramIndex].enum.push('');
};

const removeEnumValue = (paramIndex, enumIndex) => {
    parameters.value[paramIndex].enum.splice(enumIndex, 1);
    updateSchema();
};

const updateSchema = () => {
    emit('update:modelValue', generatedSchema.value);
};

// Watch for changes and emit updates
watch(generatedSchema, (newSchema) => {
    emit('update:modelValue', newSchema);
}, { deep: true });
</script>
