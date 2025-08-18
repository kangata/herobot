<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Chat Window -->
        <div
            v-show="showChat"
            class="absolute bottom-16 right-0 w-[350px] h-[500px] bg-white rounded-lg shadow-xl border border-gray-200 flex flex-col transition-all duration-300 ease-in-out"
            :class="showChat ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-2'"
        >
            <!-- Chat Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-indigo-600 text-white rounded-t-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-medium">{{ bot.name }}</div>
                        <div class="text-xs text-blue-100">Online</div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Clear Chat Button -->
                    <button
                        @click="clearChat"
                        class="text-blue-100 hover:text-white"
                        title="Clear chat"
                        :disabled="!messages || messages.length === 0"
                        :class="{ 'opacity-50 cursor-not-allowed': !messages || messages.length === 0 }"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"></path></svg>
                    </button>
                    <!-- Close Chat Button -->
                    <button @click="showChat = false" class="text-blue-100 hover:text-white">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Chat Messages -->
            <div 
                ref="chatContainer"
                class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
            >
                <!-- Welcome Message -->
                <div v-if="!messages || messages.length === 0" class="flex justify-start">
                    <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm border">
                        <div class="text-sm text-gray-800">
                            You can start testing your bot by typing a message below.
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div 
                    v-for="(message, index) in messages"
                    :key="message.id || index"
                    class="flex"
                    :class="message.isUser ? 'justify-end' : 'justify-start'"
                >
                    <div 
                        class="rounded-lg p-3 max-w-xs shadow-sm"
                        :class="{
                            'bg-indigo-500 text-white': message.isUser,
                            'bg-white text-gray-800 border': message.isBot,
                            'bg-blue-50 text-blue-800 border border-blue-200': message.isToolCall,
                            'bg-green-50 text-green-800 border border-green-200': message.isToolResponse
                        }"
                    >
                        <div class="text-sm" v-html="message.content"></div>
                        <div v-if="message.timestamp" class="text-xs mt-1 opacity-70">
                            {{ formatTime(message.timestamp) }}
                        </div>
                        <!-- Tool Details Button -->
                        <button 
                            v-if="message.isToolCall || message.isToolResponse"
                            @click="showToolDetails(message)"
                            class="mt-2 text-xs px-2 py-1 rounded bg-opacity-20 hover:bg-opacity-30 transition-colors"
                            :class="{
                                'bg-blue-500 text-blue-700': message.isToolCall,
                                'bg-green-500 text-green-700': message.isToolResponse
                            }"
                        >
                            View Details
                        </button>
                    </div>
                </div>

                <!-- Loading Message -->
                <div v-if="isLoading" class="flex justify-start">
                    <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm border">
                        <div class="flex items-center space-x-2">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-200 bg-white rounded-b-lg">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                    <input
                        v-model="newMessage"
                        type="text"
                        placeholder="Send a message..."
                        class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        :disabled="isLoading"
                        maxlength="1000"
                    />
                    <button
                        type="submit"
                        :disabled="!newMessage.trim() || isLoading"
                        class="bg-indigo-600 text-white p-2 rounded-full hover:bg-indigo-500 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg class="w-4 h-4 rotate-90" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-3 bg-red-50 border-t border-red-200 rounded-b-lg">
                <div class="text-red-700 text-xs">
                    {{ error }}
                </div>
            </div>
        </div>

        <!-- Chat Toggle Button -->
        <button
            @click="toggleChat"
            class="w-14 h-14 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 ease-in-out hover:scale-110"
            :class="showChat ? 'rotate-180' : ''"
        >
            <svg v-if="!showChat" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Tool Details Modal -->
        <div 
            v-if="showToolModal" 
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50"
            @click="closeToolModal"
        >
            <div 
                class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <span v-if="selectedToolData?.isToolCall">🔧 Tool Call Details</span>
                        <span v-else-if="selectedToolData?.isToolResponse">📋 Tool Response Details</span>
                    </h3>
                    <button 
                        @click="closeToolModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    <!-- Tool Call Details -->
                    <div v-if="selectedToolData?.isToolCall && selectedToolData?.toolCalls">
                        <div v-for="(toolCall, index) in selectedToolData.toolCalls" :key="index" class="mb-6">
                            <div class="mb-4">
                                <h4 class="font-medium text-gray-900 mb-2">Function Name</h4>
                                <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                    {{ toolCall.function?.name || 'Unknown' }}
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h4 class="font-medium text-gray-900 mb-2">Parameters</h4>
                                <div class="bg-gray-50 rounded p-3 font-mono text-sm whitespace-pre-wrap">
                                    {{ formatToolParameters(toolCall) }}
                                </div>
                            </div>

                            <div v-if="toolCall.id" class="mb-4">
                                <h4 class="font-medium text-gray-900 mb-2">Call ID</h4>
                                <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                    {{ toolCall.id }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tool Response Details -->
                    <div v-if="selectedToolData?.isToolResponse">
                        <div class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Tool Name</h4>
                            <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                {{ selectedToolData.originalMessage?.metadata?.tool_name || 'Unknown' }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Execution Status</h4>
                            <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                <span :class="{
                                    'text-green-600': selectedToolData.originalMessage?.metadata?.execution_status === 'completed',
                                    'text-red-600': selectedToolData.originalMessage?.metadata?.error,
                                    'text-yellow-600': selectedToolData.originalMessage?.metadata?.execution_status !== 'completed'
                                }">
                                    {{ selectedToolData.originalMessage?.metadata?.execution_status || 'Unknown' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedToolData.originalMessage?.tool_call_id" class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Tool Call ID</h4>
                            <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                {{ selectedToolData.originalMessage.tool_call_id }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Response Data</h4>
                            <div class="bg-gray-50 rounded p-3 font-mono text-sm whitespace-pre-wrap max-h-60 overflow-y-auto">
                                {{ formatToolResponseData(selectedToolData.originalMessage) }}
                            </div>
                        </div>

                        <div v-if="selectedToolData.originalMessage?.metadata?.execution_time" class="mb-4">
                            <h4 class="font-medium text-gray-900 mb-2">Execution Time</h4>
                            <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                                {{ selectedToolData.originalMessage.metadata.execution_time }} ms
                            </div>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div v-if="selectedToolData?.timestamp" class="mt-6 pt-4 border-t border-gray-200">
                        <h4 class="font-medium text-gray-900 mb-2">Timestamp</h4>
                        <div class="bg-gray-50 rounded p-3 font-mono text-sm">
                            {{ selectedToolData.timestamp.toLocaleString() }}
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end p-4 border-t border-gray-200">
                    <button 
                        @click="closeToolModal"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, watch, computed, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    bot: {
        type: Object,
        required: true
    },
    chatHistories: {
        type: Array,
        default: () => []
    }
});

// Reactive variables
const showChat = ref(false);
const newMessage = ref('');
const isLoading = ref(false);
const error = ref(null);
const chatContainer = ref(null);
const showToolModal = ref(false);
const selectedToolData = ref(null);
const tempMessages = ref([]);

// Computed property to process chat histories into display messages
const messages = computed(() => {
    const historyMessages = !props.chatHistories || props.chatHistories.length === 0 ? [] : 
        props.chatHistories
            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
            .map(message => {
            const baseMessage = {
                id: message.id,
                timestamp: new Date(message.created_at),
                role: message.role,
                messageType: message.message_type
            };

            switch (message.message_type) {
                case 'text':
                    return {
                        ...baseMessage,
                        content: message.message || message.raw_content || '',
                        isUser: message.role === 'user',
                        isBot: message.role === 'assistant'
                    };

                case 'tool_call':
                    return {
                        ...baseMessage,
                        content: formatToolCall(message.tool_calls),
                        isUser: false,
                        isBot: true,
                        isToolCall: true,
                        toolCalls: message.tool_calls,
                        originalMessage: message
                    };

                case 'tool_response':
                    return {
                        ...baseMessage,
                        content: formatToolResponse(message),
                        isUser: false,
                        isBot: false,
                        isToolResponse: true,
                        toolName: message.metadata?.tool_name,
                        originalMessage: message
                    };

                default:
                    return {
                        ...baseMessage,
                        content: message.message || 'Unknown message type',
                        isUser: message.role === 'user',
                        isBot: message.role === 'assistant'
                    };
            }
        });

    // Combine history messages with temporary messages
    return [...historyMessages, ...tempMessages.value];
});

// Helper functions to format different message types
const formatToolCall = (toolCalls) => {
    if (!toolCalls || toolCalls.length === 0) {
        return 'Executing function...';
    }
    
    return toolCalls.map(call => {
        const functionName = call.function?.name || 'Unknown function';
        return `🔧 Calling: ${functionName}`;
    }).join('<br>');
};

const formatToolResponse = (message) => {
    const toolName = message.metadata?.tool_name || 'Tool';
    const executionStatus = message.metadata?.execution_status || 'completed';
    
    if (message.metadata?.error) {
        return `❌ ${toolName}: Error occurred`;
    }
    
    if (executionStatus === 'completed') {
        return `✅ ${toolName}: Completed successfully`;
    }
    
    return `⏳ ${toolName}: ${executionStatus}`;
};

// Modal functions
const showToolDetails = (message) => {
    selectedToolData.value = message;
    showToolModal.value = true;
};

const closeToolModal = () => {
    showToolModal.value = false;
    selectedToolData.value = null;
};

const formatToolParameters = (toolCall) => {
    if (!toolCall?.function?.arguments) return 'No parameters';
    
    try {
        const args = JSON.parse(toolCall.function.arguments);
        return JSON.stringify(args, null, 2);
    } catch (e) {
        return toolCall.function.arguments;
    }
};

const formatToolResponseData = (message) => {
    if (!message.message) return 'No response data';
    
    try {
        const data = JSON.parse(message.message);
        return JSON.stringify(data, null, 2);
    } catch (e) {
        return message.message;
    }
};

// Methods
const toggleChat = () => {
    showChat.value = !showChat.value;
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const clearChat = () => {
    if (!confirm('Are you sure you want to clear the chat history? This action cannot be undone.')) {
        return;
    }

    error.value = null;
    isLoading.value = false;
    tempMessages.value = []; // Clear temporary messages too

    // Use Inertia to call the clear chat endpoint
    const form = useForm({});
    
    form.delete(route('bots.clear-chat', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Chat will be automatically updated when the page reloads with new chatHistories
        },
        onError: (errors) => {
            console.error('Error clearing chat:', errors);
            error.value = 'Failed to clear chat history';
        }
    });
};

// Helper function to format timestamp
const formatTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
};

const handleChatResponse = (response) => {
    if (response.success) {
        // Since messages is now computed from chatHistories, 
        // the new message should be added to chatHistories by the parent component
        error.value = null;
    } else {
        error.value = response.error;
    }
    isLoading.value = false;
    scrollToBottom();
};

const sendMessage = () => {
    if (!newMessage.value.trim() || isLoading.value) return;

    const userMessage = newMessage.value.trim();
    newMessage.value = '';
    error.value = null;

    // Add user message to display immediately
    const tempUserMessage = {
        id: `temp-${Date.now()}`,
        content: userMessage,
        isUser: true,
        isBot: false,
        timestamp: new Date(),
        role: 'user',
        messageType: 'text'
    };

    tempMessages.value.push(tempUserMessage);
    scrollToBottom();
    isLoading.value = true;

    // Create form and submit using Inertia
    const form = useForm({
        message: userMessage,
    });

    form.post(route('bots.test-message', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Clear temporary messages when we get the response
            tempMessages.value = [];
        },
        onError: (errors) => {
            console.error('Error sending message:', errors);
            error.value = 'Failed to get response from bot';
            isLoading.value = false;
            // Remove the temporary message on error
            tempMessages.value = tempMessages.value.filter(msg => msg.id !== tempUserMessage.id);
        }
    });
};

// Watch for changes in chatHistories to scroll to bottom
watch(() => props.chatHistories, () => {
    nextTick(() => {
        scrollToBottom();
    });
}, { deep: true });

// Watch for flash data changes to handle chat responses
const page = usePage();
watch(() => page.props.flash, (newFlash) => {
    const response = newFlash?.chatResponse;
    if (response && isLoading.value) {
        handleChatResponse(response);
    }
}, { deep: true, immediate: true });
</script>

<style scoped>
/* Custom scrollbar for chat container */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
