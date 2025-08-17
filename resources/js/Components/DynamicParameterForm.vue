<template>
    <div class="space-y-4">
        <div v-for="(field, key) in schemaFields" :key="key" class="space-y-2">
            <InputLabel :for="key" :value="getFieldLabel(key, field)" />
            
            <!-- String/Text Input -->
            <TextInput
                v-if="field.type === 'string' && !field.enum"
                :id="key"
                v-model="modelValue[key]"
                type="text"
                class="mt-1 block w-full"
                :placeholder="field.description || `Enter ${key}`"
                :required="isRequired(key)"
            />
            
            <!-- Number Input -->
            <TextInput
                v-else-if="field.type === 'number' || field.type === 'integer'"
                :id="key"
                v-model.number="modelValue[key]"
                type="number"
                class="mt-1 block w-full"
                :placeholder="field.description || `Enter ${key}`"
                :required="isRequired(key)"
                :min="field.minimum"
                :max="field.maximum"
                :step="field.type === 'integer' ? 1 : 'any'"
            />
            
            <!-- Boolean Checkbox -->
            <div v-else-if="field.type === 'boolean'" class="flex items-center">
                <input
                    :id="key"
                    v-model="modelValue[key]"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label :for="key" class="ml-2 block text-sm text-gray-900">
                    {{ field.description || `Enable ${key}` }}
                </label>
            </div>
            
            <!-- Enum Select -->
            <select
                v-else-if="field.enum"
                :id="key"
                v-model="modelValue[key]"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :required="isRequired(key)"
            >
                <option value="">Select {{ key }}</option>
                <option v-for="option in field.enum" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
            
            <!-- Array Input -->
            <div v-else-if="field.type === 'array'" class="space-y-2">
                <div v-for="(item, index) in getArrayValue(key)" :key="index" class="flex items-center space-x-2">
                    <TextInput
                        v-model="modelValue[key][index]"
                        type="text"
                        class="flex-1"
                        :placeholder="`${key} item ${index + 1}`"
                    />
                    <button
                        type="button"
                        @click="removeArrayItem(key, index)"
                        class="text-red-600 hover:text-red-800"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <button
                    type="button"
                    @click="addArrayItem(key)"
                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                >
                    + Add {{ key }} item
                </button>
            </div>
            
            <!-- Object Input (JSON) -->
            <TextArea
                v-else-if="field.type === 'object'"
                :id="key"
                v-model="objectValues[key]"
                @input="updateObjectValue(key, $event.target.value)"
                class="mt-1 block w-full font-mono text-sm"
                rows="3"
                :placeholder="`Enter ${key} as JSON object`"
                :required="isRequired(key)"
            />
            
            <!-- Fallback Text Input -->
            <TextInput
                v-else
                :id="key"
                v-model="modelValue[key]"
                type="text"
                class="mt-1 block w-full"
                :placeholder="field.description || `Enter ${key}`"
                :required="isRequired(key)"
            />
            
            <!-- Field Description -->
            <p v-if="field.description" class="text-sm text-gray-500">
                {{ field.description }}
            </p>
            
            <!-- Validation Error -->
            <InputError v-if="errors[key]" :message="errors[key]" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    schema: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Object,
        default: () => ({})
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

// Handle object values separately for JSON parsing
const objectValues = ref({});

const schemaFields = computed(() => {
    return props.schema.properties || {};
});

const requiredFields = computed(() => {
    return props.schema.required || [];
});

const isRequired = (fieldName) => {
    return requiredFields.value.includes(fieldName);
};

const getFieldLabel = (key, field) => {
    const label = field.title || key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
    return isRequired(key) ? `${label} *` : label;
};

const getArrayValue = (key) => {
    if (!props.modelValue[key]) {
        emit('update:modelValue', { ...props.modelValue, [key]: [] });
        return [];
    }
    return props.modelValue[key];
};

const addArrayItem = (key) => {
    const currentArray = props.modelValue[key] || [];
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: [...currentArray, '']
    });
};

const removeArrayItem = (key, index) => {
    const currentArray = [...(props.modelValue[key] || [])];
    currentArray.splice(index, 1);
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: currentArray
    });
};

const updateObjectValue = (key, value) => {
    try {
        const parsed = JSON.parse(value);
        emit('update:modelValue', {
            ...props.modelValue,
            [key]: parsed
        });
    } catch (e) {
        // Keep the string value for editing, validation will catch invalid JSON
        objectValues.value[key] = value;
    }
};

// Initialize object values for existing data
watch(() => props.modelValue, (newValue) => {
    Object.keys(schemaFields.value).forEach(key => {
        const field = schemaFields.value[key];
        if (field.type === 'object' && newValue[key]) {
            objectValues.value[key] = JSON.stringify(newValue[key], null, 2);
        }
    });
}, { immediate: true });

// Initialize default values based on schema
watch(() => props.schema, (newSchema) => {
    if (!newSchema.properties) return;
    
    const initialValues = { ...props.modelValue };
    
    Object.keys(newSchema.properties).forEach(key => {
        const field = newSchema.properties[key];
        
        if (initialValues[key] === undefined) {
            if (field.type === 'boolean') {
                initialValues[key] = field.default || false;
            } else if (field.type === 'array') {
                initialValues[key] = field.default || [];
            } else if (field.type === 'object') {
                initialValues[key] = field.default || {};
                objectValues.value[key] = JSON.stringify(field.default || {}, null, 2);
            } else if (field.type === 'number' || field.type === 'integer') {
                initialValues[key] = field.default || '';
            } else {
                initialValues[key] = field.default || '';
            }
        }
    });
    
    emit('update:modelValue', initialValues);
}, { immediate: true });
</script>
