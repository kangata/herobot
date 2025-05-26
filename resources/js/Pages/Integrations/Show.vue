<template>
    <AppLayout :title="integration.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ integration.name }}</h1>
                    <div class="mt-2">
                        <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded capitalize">{{ integration.type }}</div>
                    </div>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <PrimaryButton :href="route('integrations.edit', integration.id)">
                        <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Edit integration
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <hr class="mb-4" />

        <div v-if="!integration.is_connected" class="flex justify-between items-center max-w-4xl mx-auto">
            <div class="w-1/2 pr-8 flex flex-col justify-center">
                <h2 class="text-2xl font-semibold mb-4">Connect WhatsApp to Your Bot</h2>
                <p class="mb-6 text-gray-600">Follow these steps to link your WhatsApp account with our bot system:</p>
                <ol class="list-decimal list-inside space-y-2 mb-6">
                    <li><strong>Open WhatsApp</strong> on your phone</li>
                    <li>Tap <strong>Menu</strong> <span class="inline-block px-1 border rounded">⋮</span> on Android, or <strong>Settings</strong> <span class="inline-block px-1 border rounded">⚙</span> on iPhone</li>
                    <li>Tap <strong>Linked devices</strong> and then <strong>Link a device</strong></li>
                    <li><strong>Point your phone</strong> at this screen to capture the QR code</li>
                </ol>
            </div>
            <div v-if="whatsapp.qr" class="w-1/2 flex flex-col justify-center">
                <div class="bg-white p-4 rounded-lg shadow-md relative">
                    <img 
                        :src="whatsapp.qr" 
                        alt="WhatsApp Integration QR Code" 
                        class="w-full h-auto transition-all duration-300"
                        :class="{ 'blur-sm': isQRExpired }"
                    />
                    
                    <!-- Overlay for expired QR -->
                    <div v-if="isQRExpired" class="absolute inset-0 flex flex-col items-center justify-center bg-black bg-opacity-40 rounded-lg">
                        <div class="text-center bg-white rounded-lg p-6 shadow-lg mx-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">QR Code Timed Out</h3>
                            <p class="text-sm text-gray-600 mb-4">This QR code has timed out after 2 minutes of inactivity. Please generate a new one to continue.</p>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:text-sm transition-colors duration-200"
                                @click="refreshQR"
                                :disabled="isRefreshingQR"
                            >
                                <span class="flex items-center" v-if="isRefreshingQR">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Generating...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Generate New QR
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">WhatsApp Connected</h3>
                        <div class="mt-2 max-w-xl text-sm text-gray-500">
                            <p>Your WhatsApp account is successfully linked to the bot.</p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex sm:flex-shrink-0 sm:items-center">
                        <CheckCircleIcon class="h-8 w-8 text-green-400" aria-hidden="true" />
                    </div>
                </div>
                <div class="mt-5">
                    <div class="rounded-md bg-gray-50 px-6 py-5">
                        <div class="sm:flex sm:items-center sm:justify-between">
                            <div class="sm:flex sm:items-center">
                                <PhoneIcon class="h-8 w-8 text-gray-400" aria-hidden="true" />
                                <div class="ml-3">
                                    <h4 class="text-lg font-medium text-gray-900">{{ $filters.formatPhoneNumber(integration.phone) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 font-medium text-red-700 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:text-sm"
                        @click="disconnectWhatsApp"
                        :disabled="isDisconnecting"
                    >
                        <span class="flex items-center" v-if="isDisconnecting">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Disconnecting...
                        </span>
                        <span v-else>Disconnect WhatsApp</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { PencilIcon, CheckCircleIcon, PhoneIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    integration: Object,
    whatsapp: {
        type: Object,
        default: () => ({
            qr: null,
            status: null
        })
    }
});

onMounted(() => {
    router.reload({
        only: [
            'whatsapp'
        ]
    });

    window.Echo.private(`integration.${props.integration.id}`)
        .listen('IntegrationUpdated', ({ status }) => {
            if (status === 'qr_expired') {
                isQRExpired.value = true;
                router.reload({
                    only: [
                        'integration'
                    ],
                });
            } else {
                isQRExpired.value = false;
                router.reload({
                    only: [
                        'integration',
                        'whatsapp'
                    ],
                });
            }
        });
});

onUnmounted(() => {
    window.Echo.leave(`integration.${props.integration.id}`);
});

const isDisconnecting = ref(false);
const isRefreshingQR = ref(false);
const isQRExpired = ref(false);

const disconnectWhatsApp = () => {
    isDisconnecting.value = true;
    router.post(route('integrations.disconnect', props.integration.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    })
};

const refreshQR = () => {
    isRefreshingQR.value = true;
    isQRExpired.value = false;
    router.reload({
        only: [
            'whatsapp'
        ],
        onFinish: () => {
            isRefreshingQR.value = false;
        },
    });
};
</script>
