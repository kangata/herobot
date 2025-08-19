<template>
    <div class="space-y-6">
        <!-- URL Configuration -->
        <div>
            <InputLabel for="url" value="API Endpoint URL" />
            <TextInput
                id="url"
                v-model="config.url"
                type="url"
                class="mt-1 block w-full"
                placeholder="https://api.example.com/endpoint"
                required
                @input="updateConfig"
            />
            <p class="mt-1 text-sm text-gray-500">
                The base URL for the API endpoint
            </p>
        </div>

        <!-- HTTP Method -->
        <div>
            <InputLabel for="method" value="HTTP Method" />
            <select
                id="method"
                v-model="config.method"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                @change="updateConfig"
            >
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>
        </div>

        <!-- Headers -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <InputLabel value="Headers" />
                <button
                    type="button"
                    @click="addHeader"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                    + Add Header
                </button>
            </div>
            <div class="space-y-2">
                <div
                    v-for="(header, index) in config.headers"
                    :key="index"
                    class="flex items-center space-x-2 align-center"
                >
                    <TextInput
                        v-model="header.key"
                        placeholder="Header name"
                        class="flex-1"
                        @input="updateConfig"
                    />
                    <TextInput
                        v-model="header.value"
                        placeholder="Header value"
                        class="flex-1"
                        @input="updateConfig"
                    />
                    <button
                        type="button"
                        @click="removeHeader(index)"
                        class="text-red-600 hover:text-red-500 mt-2"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                HTTP headers to include with the request
            </p>
        </div>

        <!-- Query Parameters -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <InputLabel value="Query Parameters" />
                <button
                    type="button"
                    @click="addQueryParam"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                    + Add Parameter
                </button>
            </div>
            <div class="space-y-2">
                <div
                    v-for="(param, index) in config.query"
                    :key="index"
                    class="flex items-center space-x-2 align-center"
                >
                    <TextInput
                        v-model="param.key"
                        placeholder="Parameter name"
                        class="flex-1"
                        @input="updateConfig"
                    />
                    <TextInput
                        v-model="param.value"
                        placeholder="Parameter value (use {{variable}} for dynamic values)"
                        class="flex-1"
                        @input="updateConfig"
                    />
                    <button
                        type="button"
                        @click="removeQueryParam(index)"
                        class="text-red-600 hover:text-red-500 mt-2"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                URL query parameters. Use <code class="bg-gray-100 px-1 rounded">&#123;&#123;variable&#125;&#125;</code> for dynamic values
            </p>
        </div>

        <!-- Request Body (for POST/PUT/PATCH) -->
        <div v-if="['POST', 'PUT', 'PATCH'].includes(config.method)">
            <InputLabel value="Request Body" />
            <div class="mt-2 space-y-3">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="bodyType"
                            value="json"
                            class="mr-2"
                            @change="updateBodyType"
                        />
                        JSON
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="bodyType"
                            value="form"
                            class="mr-2"
                            @change="updateBodyType"
                        />
                        Form Data
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="bodyType"
                            value="raw"
                            class="mr-2"
                            @change="updateBodyType"
                        />
                        Raw
                    </label>
                </div>

                <!-- JSON Body -->
                <div v-if="bodyType === 'json'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">JSON Fields</span>
                        <button
                            type="button"
                            @click="addBodyField"
                            class="text-sm text-indigo-600 hover:text-indigo-500"
                        >
                            + Add Field
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(field, index) in config.body"
                            :key="index"
                            class="flex items-center space-x-2"
                        >
                            <TextInput
                                v-model="field.key"
                                placeholder="Field name"
                                class="flex-1"
                                @input="updateConfig"
                            />
                            <TextInput
                                v-model="field.value"
                                placeholder="Field value (use {{variable}} for dynamic values)"
                                class="flex-1"
                                @input="updateConfig"
                            />
                            <button
                                type="button"
                                @click="removeBodyField(index)"
                                class="text-red-600 hover:text-red-500"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Raw Body -->
                <div v-else-if="bodyType === 'raw'">
                    <TextArea
                        v-model="config.rawBody"
                        class="mt-1 block w-full font-mono text-sm"
                        rows="4"
                        placeholder="Raw request body content"
                        @input="updateConfig"
                    />
                </div>
            </div>
        </div>

        <!-- Authentication -->
        <div>
            <InputLabel value="Authentication" />
            <div class="mt-2 space-y-3">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="authType"
                            value="none"
                            class="mr-2"
                            @change="updateAuthType"
                        />
                        None
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="authType"
                            value="bearer"
                            class="mr-2"
                            @change="updateAuthType"
                        />
                        Bearer Token
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="authType"
                            value="api_key"
                            class="mr-2"
                            @change="updateAuthType"
                        />
                        API Key
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            v-model="authType"
                            value="basic"
                            class="mr-2"
                            @change="updateAuthType"
                        />
                        Basic Auth
                    </label>
                </div>

                <!-- Bearer Token -->
                <div v-if="authType === 'bearer'" class="grid grid-cols-1 gap-2">
                    <TextInput
                        v-model="config.auth.token"
                        placeholder="Bearer token (use {{token}} for dynamic value)"
                        @input="updateConfig"
                    />
                </div>

                <!-- API Key -->
                <div v-else-if="authType === 'api_key'" class="grid grid-cols-2 gap-2">
                    <TextInput
                        v-model="config.auth.key_name"
                        placeholder="API key parameter name"
                        @input="updateConfig"
                    />
                    <TextInput
                        v-model="config.auth.key_value"
                        placeholder="API key value (use {{api_key}} for dynamic value)"
                        @input="updateConfig"
                    />
                </div>

                <!-- Basic Auth -->
                <div v-else-if="authType === 'basic'" class="grid grid-cols-2 gap-2">
                    <TextInput
                        v-model="config.auth.username"
                        placeholder="Username (use {{username}} for dynamic value)"
                        @input="updateConfig"
                    />
                    <TextInput
                        v-model="config.auth.password"
                        placeholder="Password (use {{password}} for dynamic value)"
                        type="password"
                        @input="updateConfig"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const config = reactive({
    url: '',
    method: 'GET',
    headers: [],
    query: [],
    body: [],
    rawBody: '',
    auth: {}
});

// Initialize config from modelValue
const initializeConfig = (modelValue) => {
    if (!modelValue || Object.keys(modelValue).length === 0) return;
    
    config.url = modelValue.url || '';
    config.method = modelValue.method || 'GET';
    config.rawBody = modelValue.rawBody || '';
    config.auth = modelValue.auth || {};
    
    // Set body type based on existing data
    if (modelValue.bodyType) {
        bodyType.value = modelValue.bodyType;
    } else if (modelValue.body) {
        bodyType.value = typeof modelValue.body === 'string' ? 'raw' : 'json';
    }
    
    // Set auth type based on existing data
    if (modelValue.authType) {
        authType.value = modelValue.authType;
    } else if (modelValue.auth) {
        if (modelValue.auth.token) {
            authType.value = 'bearer';
        } else if (modelValue.auth.key_name) {
            authType.value = 'api_key';
        } else if (modelValue.auth.username) {
            authType.value = 'basic';
        } else {
            authType.value = 'none';
        }
    }
    
    // Convert headers object to array format for GUI
    if (modelValue.headers) {
        if (Array.isArray(modelValue.headers)) {
            config.headers = [...modelValue.headers];
        } else {
            config.headers = Object.entries(modelValue.headers).map(([key, value]) => ({ key, value }));
        }
    } else {
        config.headers = [];
    }
    
    // Convert query object to array format for GUI
    if (modelValue.query) {
        if (Array.isArray(modelValue.query)) {
            config.query = [...modelValue.query];
        } else {
            config.query = Object.entries(modelValue.query).map(([key, value]) => ({ key, value }));
        }
    } else {
        config.query = [];
    }
    
    // Convert body object to array format for GUI
    if (modelValue.body) {
        if (Array.isArray(modelValue.body)) {
            config.body = [...modelValue.body];
        } else if (typeof modelValue.body === 'object') {
            config.body = Object.entries(modelValue.body).map(([key, value]) => ({ key, value }));
        } else {
            config.rawBody = modelValue.body;
        }
    } else {
        config.body = [];
    }
};

// Initialize on mount
onMounted(() => {
    initializeConfig(props.modelValue);
});

// Watch for changes to modelValue (only on initial load, not during editing)
watch(() => props.modelValue, (newValue) => {
    // Only initialize if we're going from empty to having data (initial load)
    if (newValue && Object.keys(newValue).length > 0 && 
        config.headers.length === 0 && config.query.length === 0 && config.body.length === 0) {
        initializeConfig(newValue);
    }
}, { deep: true });

const bodyType = ref('json');
const authType = ref('none');

// Header management
const addHeader = () => {
    config.headers.push({ key: '', value: '' });
    updateConfig();
};

const removeHeader = (index) => {
    config.headers.splice(index, 1);
    updateConfig();
};

// Query parameter management
const addQueryParam = () => {
    config.query.push({ key: '', value: '' });
    updateConfig();
};

const removeQueryParam = (index) => {
    config.query.splice(index, 1);
    updateConfig();
};

// Body field management
const addBodyField = () => {
    config.body.push({ key: '', value: '' });
    updateConfig();
};

const removeBodyField = (index) => {
    config.body.splice(index, 1);
    updateConfig();
};

const updateBodyType = () => {
    if (bodyType.value !== 'json') {
        config.body = [];
    }
    if (bodyType.value !== 'raw') {
        config.rawBody = '';
    }
    updateConfig();
};

const updateAuthType = () => {
    config.auth = {};
    updateConfig();
};

const updateConfig = () => {
    // Convert arrays to objects for the final config
    const headers = {};
    config.headers.forEach(header => {
        if (header.key && header.value) {
            headers[header.key] = header.value;
        }
    });

    const query = {};
    config.query.forEach(param => {
        if (param.key && param.value) {
            query[param.key] = param.value;
        }
    });

    const body = bodyType.value === 'json' ? {} : config.rawBody;
    if (bodyType.value === 'json') {
        config.body.forEach(field => {
            if (field.key && field.value) {
                body[field.key] = field.value;
            }
        });
    }

    const finalConfig = {
        url: config.url,
        method: config.method,
        headers,
        query,
        body,
        auth: config.auth,
        bodyType: bodyType.value,
        authType: authType.value
    };

    emit('update:modelValue', finalConfig);
};

// Initialize arrays from objects if they exist
if (props.modelValue.headers && typeof props.modelValue.headers === 'object') {
    config.headers = Object.entries(props.modelValue.headers).map(([key, value]) => ({ key, value }));
}

if (props.modelValue.query && typeof props.modelValue.query === 'object') {
    config.query = Object.entries(props.modelValue.query).map(([key, value]) => ({ key, value }));
}

if (props.modelValue.body && typeof props.modelValue.body === 'object' && bodyType.value === 'json') {
    config.body = Object.entries(props.modelValue.body).map(([key, value]) => ({ key, value }));
}
</script>
