<template>
    <AppLayout :title="integration.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ integration.name }}</h1>
                    <div class="mt-2">
                        <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded">Whatsapp</div>
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
            <div v-if="qrCode" class="w-1/2 flex flex-col justify-center">
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <img :src="qrCode" alt="WhatsApp Integration QR Code" class="w-full h-auto" />
                </div>
                <p class="mt-4 text-sm text-gray-500">
                    This QR code will expire in 10 minutes. If it expires, please refresh the page to generate a new one.
                </p>
            </div>
        </div>

        <div v-else class="w-full mx-auto">
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Connected</p>
                <p>Your WhatsApp account is successfully linked to the bot.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { PencilIcon } from "@heroicons/vue/24/outline";
import { ref, onMounted } from 'vue';

const props = defineProps({
    integration: Object,
});

const qrCode = ref('');

const fetchQRCode = async () => {
    try {
        const response = await fetch(route('integrations.qr', props.integration.id));
        const data = await response.json();
        qrCode.value = data.qr;
    } catch (error) {
        console.error('Error fetching QR code:', error);
    }
};

onMounted(() => {
    if (!props.integration.is_connected) {
        fetchQRCode();
    }
});
</script>
