<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <h1 class="sr-only">Log in to your Herobot account</h1>
        <form @submit.prevent="submit" class="w-full max-w-sm">
            <div class="mb-6">
                <InputLabel for="email" value="Email" />
                <TextInput id="email" v-model="form.email" type="email" required autofocus />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div class="mb-6">
                <InputLabel for="password" value="Password" />
                <TextInput id="password" v-model="form.password" type="password" required
                    autocomplete="current-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Log in
            </PrimaryButton>

            <p class="mt-8 text-center">
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm hover:underline">
                Forgot your password?
                </Link>
            </p>
        </form>

        <footer class="relative shrink-0 mt-12">
            <div
                class="space-y-4 text-sm text-gray-900 sm:flex sm:items-center sm:justify-center sm:space-y-0 sm:space-x-4">
                <p class="text-center sm:text-left">Don't have an account?</p>
                <Link class="inline-flex justify-center rounded-lg text-sm font-semibold py-2.5 px-4 text-slate-900 ring-1 ring-slate-900/10 hover:ring-slate-900/20"
                    href="/early-access">
                    <span>Get Early Access <span aria-hidden="true">→</span></span>
                </Link>
            </div>
        </footer>
    </AuthenticationCard>
</template>
