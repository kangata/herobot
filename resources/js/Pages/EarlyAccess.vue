<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    organization_name: '',
    email: '',
    website: '',
    organization_type: '',
    description: '',
});

const submit = () => {
    form.post(route('early-access.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Herobot - Early Access" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="form.recentlySuccessful" class="mb-4 font-medium text-sm text-green-600">
            Your application has been submitted successfully. We will contact you soon!
        </div>

        <div class="border border-gray-300 p-4 mb-6 rounded-lg">
            <p class="font-medium">
                Free Access Available!
            </p>
            <p class="text-sm mt-1">
                We offer free access for educational institutions and social activities.
            </p>
        </div>

        <form @submit.prevent="submit" class="w-full max-w-md">
            <div class="mb-6">
                <InputLabel for="organization_name" value="Organization Name" />
                <TextInput
                    id="organization_name"
                    v-model="form.organization_name"
                    type="text"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.organization_name" />
            </div>

            <div class="mb-6">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mb-6">
                <InputLabel for="website" value="Website" />
                <TextInput
                    id="website"
                    v-model="form.website"
                    type="text"
                    required
                />
                <InputError class="mt-2" :message="form.errors.website" />
            </div>

            <div class="mb-6">
                <InputLabel for="organization_type" value="Organization Type" />
                <select
                    id="organization_type"
                    v-model="form.organization_type"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Select type</option>
                    <option value="school">School/Educational Institution</option>
                    <option value="social">Social Organization</option>
                    <option value="business">Business</option>
                    <option value="other">Other</option>
                </select>
                <InputError class="mt-2" :message="form.errors.organization_type" />
            </div>

            <div class="mb-6">
                <InputLabel for="description" value="Description of Use Case" />
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Please describe how you plan to use our platform..."
                    required
                ></textarea>
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <PrimaryButton class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Submit Application
            </PrimaryButton>

            <p class="mt-6 text-center text-sm text-gray-500">
                We'll review your application and get back to you within 2 business days.
            </p>
        </form>

        <footer class="relative shrink-0 mt-12">
            <div
                class="space-y-4 text-sm text-gray-900 sm:flex sm:items-center sm:justify-center sm:space-y-0 sm:space-x-4">
                <p class="text-center sm:text-left">Already have an account?</p>
                <Link class="inline-flex justify-center rounded-lg text-sm font-semibold py-2.5 px-4 text-slate-900 ring-1 ring-slate-900/10 hover:ring-slate-900/20"
                    href="/login">
                    <span>Log in <span aria-hidden="true">→</span></span>
                </Link>
            </div>
        </footer>
    </AuthenticationCard>
</template> 