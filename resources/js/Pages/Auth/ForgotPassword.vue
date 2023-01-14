<script setup>
import { Head, useForm } from '@inertiajs/inertia-vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>

    <Head title="Forgot Password" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="max-w-sm">
            <h1 class="mb-2 text-center text-sm font-semibold text-gray-900">Reset your password</h1>
            <p class="mb-10 text-center text-sm">Enter your email and we'll send you a link to reset your password.</p>
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <form class="w-full max-w-sm" @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required
                        autofocus />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="flex items-center mt-4">
                    <PrimaryButton class="w-full" :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing">
                        Reset your password
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticationCard>
</template>
