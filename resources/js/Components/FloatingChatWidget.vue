<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Chat Window -->
        <div
            v-show="showChat"
            class="absolute bottom-16 right-0 w-80 h-96 bg-white rounded-lg shadow-xl border border-gray-200 flex flex-col transition-all duration-300 ease-in-out"
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
                <div v-if="messages.length === 0" class="flex justify-start">
                    <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm border">
                        <div class="text-sm text-gray-800">
                            You can start testing your bot by typing a message below.
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div 
                    v-for="(message, index) in messages"
                    :key="index"
                    class="flex"
                    :class="message.isUser ? 'justify-end' : 'justify-start'"
                >
                    <div 
                        class="rounded-lg p-3 max-w-xs shadow-sm"
                        :class="message.isUser 
                            ? 'bg-indigo-500 text-white' 
                            : 'bg-white text-gray-800 border'"
                    >
                        <div class="text-sm">{{ message.content }}</div>
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
    </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    bot: {
        type: Object,
        required: true
    }
});

// Reactive variables
const showChat = ref(false);
const messages = ref([]);
const newMessage = ref('');
const isLoading = ref(false);
const error = ref(null);
const chatContainer = ref(null);

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

const handleChatResponse = (response) => {
    if (response.success) {
        messages.value.push({
            content: response.response,
            isUser: false,
            timestamp: new Date(response.timestamp)
        });
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

    // Add user message to chat
    messages.value.push({
        content: userMessage,
        isUser: true,
        timestamp: new Date()
    });

    scrollToBottom();
    isLoading.value = true;

    // Prepare chat history for API
    const chatHistory = messages.value
        .filter(msg => !msg.isUser)
        .slice(-5) // Last 5 bot responses
        .map((msg) => ({
            message: messages.value[messages.value.findIndex(m => m === msg) - 1]?.content || '',
            response: msg.content
        }))
        .filter(item => item.message); // Remove empty messages

    // Create form and submit using Inertia
    const form = useForm({
        message: userMessage,
        chat_history: chatHistory
    });

    form.post(route('bots.test-message', props.bot.id), {
        preserveState: true,
        preserveScroll: true,
        onError: (errors) => {
            console.error('Error sending message:', errors);
            error.value = 'Failed to get response from bot';
            isLoading.value = false;
        }
    });
};

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
