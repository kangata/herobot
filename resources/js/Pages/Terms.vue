<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
</script>

<template>
    <Head title="Herobot - Terms of Service" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="prose max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8">Terms of Service</h1>

            <div class="space-y-6 text-gray-600">
                <section>
                    <h2 class="text-xl font-semibold text-gray-800">1. Acceptance of Terms</h2>
                    <p>By accessing and using Herobot's services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">2. Description of Service</h2>
                    <p>Herobot provides a multi-channel customer service platform that enables businesses to manage customer conversations across various messaging platforms including WhatsApp, Instagram, Facebook Messenger, and other supported channels.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">3. User Responsibilities</h2>
                    <p>You agree to:</p>
                    <ul class="list-disc ml-6">
                        <li>Provide accurate and complete information</li>
                        <li>Maintain the security of your account</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Not use the service for any illegal or unauthorized purpose</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">4. Data Privacy</h2>
                    <p>Your use of Herobot is also governed by our Privacy Policy. By using Herobot, you agree to the collection and use of information as detailed in our Privacy Policy.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">5. Service Modifications</h2>
                    <p>We reserve the right to modify or discontinue, temporarily or permanently, the service with or without notice.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">6. Termination</h2>
                    <p>We reserve the right to terminate or suspend your account and access to the service at our sole discretion, without notice, for conduct that we believe violates these Terms of Service or is harmful to other users, us, or third parties, or for any other reason.</p>
                </section>

                <section class="mt-8 text-sm text-gray-500">
                    <p>Last updated: {{ new Date('2025-05-29T03:12:38Z').toLocaleDateString() }}</p>
                </section>
            </div>
        </div>
    </AuthenticationCard>
</template> 