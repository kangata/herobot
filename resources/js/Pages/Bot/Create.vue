<template>
    <AppLayout title="Bot Create">
        <form @submit.prevent="submit" class="w-full max-w-xl">
            <div class="space-y-12">
                <div class="mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Create a new bot</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">This information will be used to create your bot.</p>
                </div>
            </div>

            <div class="mb-6">
                <InputLabel for="name" value="Name" />
                <TextInput id="name" v-model="form.name" type="text" required autofocus />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div class="mb-6">
                <InputLabel for="description" value="Description" />
                <TextInput id="description" v-model="form.description" type="text" required />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>
            <div class="mb-6">
                <InputLabel for="personality" value="Personality" />
                <TextArea id="personality" v-model="form.personality" required></TextArea>
                <InputError class="mt-2" :message="form.errors.personality" />
            </div>

            <div class="flex flex-row text-right">
                <PrimaryButton class="mr-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create
                </PrimaryButton>

                <SecondaryButton :href="route('bots.index')">
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextArea from '@/Components/TextArea.vue';

const form = useForm({
    name: '',
    description: '',
    personality: '',
});

const submit = () => {
    form.post(route('bots.store'));
};
</script>
