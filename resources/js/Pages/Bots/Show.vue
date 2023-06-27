<template>
    <AppLayout :title="bot.name">
        <div class="space-y-12">
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold leading-6 text-gray-900">{{ bot.name }}</h1>
                    <p class="mt-2 text-sm text-gray-700">{{ bot.description }}</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <PrimaryButton :href="route('bots.edit', bot.id)">
                        <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Edit bot
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <hr class="mb-4" />

        <!-- Integration Status -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4">
            Integration Status
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="font-medium">Bot Dihak</div>
                    <div class="text-sm text-gray-500 mt-2">+628123123123</div>
                </div>
                <div class="px-6 py-3 flex items-center text-xs">
                    <div class="flex items-center grow">
                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                        <div class="text-green-500">Connected</div>
                    </div>
                    <button class="px-4 py-2 bg-red-500 text-white rounded-md">
                        Disconnect
                    </button>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 flex items-center justify-center cursor-pointer">
                <PlusIcon class="h-6 w-6 text-gray-400" />
                <span class="ml-2">Connect new Integration</span>
            </div>
        </div>

        <!-- Knowledge Collection -->
        <h3 class="text-lg font-medium leading-7 text-gray-900 mb-4 mt-6">
            Knowledge Collection
        </h3>

        <div class="grid grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 text-base">
                <div class="p-6 border-b border-gray-900/5">
                    <div class="font-medium">App Development</div>
                    <div class="text-sm text-gray-500 mt-2">Pengetahuan tentang App</div>
                </div>
                <div class="px-6 py-3 flex text-xs">
                    <button class="grow px-4 py-2 bg-red-500 text-white rounded-md">
                        Disconnect
                    </button>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 flex items-center justify-center cursor-pointer">
                <PlusIcon class="h-6 w-6 text-gray-400" />
                <span class="ml-2">Connect new Knowledge</span>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { PencilIcon, PlusIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    bot: {
        type: Object,
        required: true,
    },
});
</script>
