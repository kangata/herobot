<template>
    <AppLayout title="Bots">
        <div>
            <div class="sm:flex sm:items-center mb-4">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Bot Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Create, edit, and delete your chatbots.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none" v-if="$page.props.bots.length > 0">
                    <PrimaryButton :href="route('bots.create')">
                        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                        Add bot
                    </PrimaryButton>
                </div>
            </div>

            <BotList :bots="$page.props.bots" />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { PlusIcon } from '@heroicons/vue/20/solid';
import BotList from './Partials/BotList.vue';
</script>

