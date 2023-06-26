<template>
    <AppLayout title="Knowledge Update">
        <form @submit.prevent="submit" class="w-full max-w-xl">
            <div class="space-y-12">
                <div class="mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Update knowledge</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Update the information of your knowledge.</p>
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
                <InputLabel for="data" value="Data" />
                <TextArea id="data" v-model="form.value" type="text" required />
                <InputError class="mt-2" :message="form.errors.value" />
            </div>

            <div class="flex flex-row text-right items-center">
                <SecondaryButton class="mr-2" @click="goBack">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="mr-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Save
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
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({
    knowledge: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    name: props.knowledge.name,
    description: props.knowledge.description,
    value: props.knowledge.data,
});

const submit = () => {
    form.put(route('knowledges.update', props.knowledge.id));
};

const goBack = () => {
    history.back();
};
</script>
