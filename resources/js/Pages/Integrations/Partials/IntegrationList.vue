<template>
    <div v-if="integrations.length > 0">
        <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
            <li v-for="integration in integrations" :key="integration.id" class="rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-900/5 relative">
                    <div class="bg-green-500 text-white inline-block py-1 px-2 text-xs rounded mb-2 capitalize">{{ integration.type }}</div>
                    <Link :href="route('integrations.show', integration.id)"
                        class="font-medium leading-6 text-gray-900 block">{{ integration.name }}</Link>
                    <div class="text-sm text-gray-500 mt-2">{{ $filters.formatPhoneNumber(integration.phone) || '-' }}</div>
                    <Menu as="div" class="absolute top-6 right-6">
                        <MenuButton class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Open options</span>
                            <EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                <MenuItem v-slot="{ active }">
                                <Link :href="route('integrations.show', integration.id)"
                                    :class="[active ? 'bg-gray-50' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900']">
                                View<span class="sr-only">, {{ integration.name }}</span></Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                <Link :href="route('integrations.edit', integration.id)"
                                    :class="[active ? 'bg-gray-50' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900']">
                                Edit<span class="sr-only">, {{ integration.name }}</span></Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                <a @click="showDeleteConfirmation(integration)"
                                    :class="[active ? 'bg-gray-50' : '', 'cursor-pointer block px-3 py-1 text-sm leading-6 text-red-500']">Delete<span
                                        class="sr-only">, {{ integration.name }}</span></a>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div class="px-6 py-4 flex items-center text-xs">
                    <div class="flex items-center grow">
                        <template v-if="integration.is_connected">
                            <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                            <div class="text-green-500">Connected</div>
                        </template>
                        <template v-else>
                            <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                            <div class="text-red-500">Disconnected</div>
                        </template>
                    </div>
                </div>
            </li>
        </ul>

        <ConfirmationModal :show="confirmingintegrationDeletion" @close="confirmingintegrationDeletion = false">
            <template #title>
                Delete integration
            </template>

            <template #content>
                Are you sure you want to delete this integration? Once a integration is deleted, all of its resources and
                data will be permanently deleted.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingintegrationDeletion = false">
                    Cancel
                </SecondaryButton>

                <DangerButton class="ml-3" :class="{ 'opacity-25': formDelete.processing }"
                    :disabled="formDelete.processing" @click="deleteintegration">
                    Delete integration
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
    <div v-else class="text-center pt-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            aria-hidden="true">
            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-semibold text-gray-900">No integration</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new integration.</p>
        <div class="mt-6">
            <PrimaryButton :href="route('integrations.create')">
                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                Add integration
            </PrimaryButton>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { PlusIcon, EllipsisHorizontalIcon } from '@heroicons/vue/20/solid'
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    integrations: {
        type: Array,
        default: () => []
    }
});

const confirmingintegrationDeletion = ref(false);
const formDelete = useForm({});
const integrationToDelete = ref(null);

const showDeleteConfirmation = (integration) => {
    integrationToDelete.value = integration;
    confirmingintegrationDeletion.value = true;
};

const deleteintegration = () => {
    formDelete.delete(route('integrations.destroy', integrationToDelete.value), {
        errorBag: 'deleteintegration',
        preserveState: true,
        preserveScroll: true,
    });
    confirmingintegrationDeletion.value = false;
};
</script>
