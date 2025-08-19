<template>
    <div v-if="tools.length > 0">
        <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
            <li v-for="tool in tools" :key="tool.id"
                class="rounded-xl border border-gray-200 relative overflow-hidden">
                <Link :href="route('tools.show', tool)"
                    class="text-white text-center w-full h-32 align-middle justify-center flex flex-col items-center"
                    :class="toolIcons[tool.type]?.class || 'bg-indigo-600'">
                    <component :is="toolIcons[tool.type]?.icon || WrenchScrewdriverIcon" class="h-6 w-6" />
                    <span class="capitalize mt-2">
                        {{ toolIcons[tool.type]?.text || tool.type }}
                    </span>
                </Link>
                <div class="absolute top-4 right-4">
                    <Menu as="div" class="relative ml-auto">
                        <MenuButton class="-m-2.5 block p-2.5 text-white hover:text-gray-200">
                            <span class="sr-only">Open options</span>
                            <EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                <MenuItem v-slot="{ active }">
                                    <Link :href="route('tools.show', tool)"
                                        :class="[active ? 'bg-gray-50' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900']">
                                    View</Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <Link :href="route('tools.edit', tool)"
                                        :class="[active ? 'bg-gray-50' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900']">
                                    Edit</Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a @click="confirmDelete(tool)"
                                        :class="[active ? 'bg-gray-50' : '', 'cursor-pointer block px-3 py-1 text-sm leading-6 text-red-500']">Delete</a>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div class="items-center gap-x-4 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <Link :href="route('tools.edit', tool)" class="font-medium leading-6 text-gray-900">
                            {{ tool.name }}
                        </Link>
                        <StatusBadge :status="tool.is_active ? 'active' : 'inactive'" />
                    </div>
                    <div class="text-sm text-gray-500 mb-2">
                        {{ tool.description }}
                    </div>
                    <div class="flex w-full text-sm leading-5 text-gray-500">
                        <span class="flex-grow">{{ tool.updated_at ? new Date(tool.updated_at).toLocaleDateString() : 'Never used' }}</span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <div v-else class="text-center pt-8">
        <WrenchScrewdriverIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-semibold text-gray-900">No tools</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new tool.</p>
        <div class="mt-6">
            <PrimaryButton :href="route('tools.create')">
                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                New Tool
            </PrimaryButton>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal :show="confirmingToolDeletion" @close="confirmingToolDeletion = false">
        <template #title>
            Delete Tool
        </template>

        <template #content>
            Are you sure you want to delete this tool? This action cannot be undone.
        </template>

        <template #footer>
            <SecondaryButton @click="confirmingToolDeletion = false">
                Cancel
            </SecondaryButton>

            <DangerButton class="ml-3" @click="deleteTool" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                Delete Tool
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { WrenchScrewdriverIcon, PlusIcon, EllipsisHorizontalIcon } from '@heroicons/vue/24/outline';
import { GlobeAltIcon, CommandLineIcon, CogIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    tools: Array,
});

const toolIcons = {
    http: { icon: GlobeAltIcon, text: 'HTTP Request', class: 'bg-blue-600' },
    function: { icon: CommandLineIcon, text: 'Function', class: 'bg-green-600' },
    webhook: { icon: GlobeAltIcon, text: 'Webhook', class: 'bg-purple-600' },
    api: { icon: CogIcon, text: 'API Tool', class: 'bg-orange-600' },
    custom: { icon: WrenchScrewdriverIcon, text: 'Custom Tool', class: 'bg-indigo-600' },
};

const confirmingToolDeletion = ref(false);
const toolToDelete = ref(null);

const deleteForm = useForm({});

const confirmDelete = (tool) => {
    toolToDelete.value = tool;
    confirmingToolDeletion.value = true;
};

const deleteTool = () => {
    deleteForm.delete(route('tools.destroy', toolToDelete.value), {
        onSuccess: () => {
            confirmingToolDeletion.value = false;
            toolToDelete.value = null;
        },
    });
};
</script>
