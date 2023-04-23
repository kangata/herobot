<template>
    <div>
        <Head :title="title" />

        <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="lg:pl-72">
            <Topbar @open-sidebar="sidebarOpen = true" />

            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <!-- Your content -->
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/inertia-vue3';
import Sidebar from './Partials/Sidebar.vue'
import Topbar from './Partials/Topbar.vue';

defineProps({
    title: String,
});

const sidebarOpen = ref(false)
</script>
