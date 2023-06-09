<template>
    <AppLayout title="Bot Update">
        <form @submit.prevent="submit" class="w-full max-w-xl">
            <div class="space-y-12">
                <div class="mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Update Bot</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Update the information of your bot.</p>
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

            <div class="flex flex-row text-right">
                <PrimaryButton class="mr-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Save
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

const props = defineProps({
    bot: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    name: props.bot.name,
    description: props.bot.description,
});

const submit = () => {
    form.put(route('bots.update', props.bot.id));
};
</script>
