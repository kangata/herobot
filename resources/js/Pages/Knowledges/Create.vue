<template>
    <AppLayout title="Knowledge Create">
        <form @submit.prevent="submit" class="w-full max-w-xl">
            <div class="space-y-12">
                <div class="mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Create a new knowledge</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">This information will be used to create your
                        knowledge.</p>
                </div>
            </div>

            <div class="mb-6">
                <InputLabel for="name" value="Name" />
                <TextInput id="name" v-model="form.name" type="text" required autofocus />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div class="mb-6">
                <InputLabel for="type" value="Type" />
                <fieldset class="mt-2">
                    <legend class="sr-only">Type</legend>
                    <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                        <div v-for="item in knowledgeTypes" :key="item.id"
                            class="flex items-center">
                            <input :id="item.id" name="notification-method" type="radio"
                                :checked="item.id === 'text'"
                                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                            <label :for="item.id"
                                class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ item.title }}</label>
                        </div>
                    </div>
                </fieldset>
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
            <div class="mb-6">
                <InputLabel for="data" value="Data" />
                <TextArea id="data" v-model="form.text" type="text" required />
                <InputError class="mt-2" :message="form.text.value" />
            </div>

            <div class="flex flex-row text-right">
                <SecondaryButton class="mr-2" :href="route('knowledges.index')">
                    Cancel
                </SecondaryButton>
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextArea from '@/Components/TextArea.vue';

const form = useForm({
    name: '',
    type: '',
    text: '',
});

const knowledgeTypes = [
    { id: 'text', title: 'Text' },
    // { id: 'qa', title: 'Question & Answer' },
    // { id: 'file', title: 'File' },
];

const submit = () => {
    form.post(route('knowledges.store'));
};
</script>
