<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
</script>

<template>
    <Head title="Herobot - Privacy Policy" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="prose max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8">Privacy Policy</h1>

            <div class="space-y-6 text-gray-600">
                <section>
                    <h2 class="text-xl font-semibold text-gray-800">1. Information We Collect</h2>
                    <p>We collect information that you provide directly to us, including:</p>
                    <ul class="list-disc ml-6">
                        <li>Account information (name, email, organization details)</li>
                        <li>Communication data from supported messaging platforms</li>
                        <li>Usage information and analytics</li>
                        <li>Customer service interactions</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">2. How We Use Your Information</h2>
                    <p>We use the collected information to:</p>
                    <ul class="list-disc ml-6">
                        <li>Provide and maintain our services</li>
                        <li>Improve and personalize user experience</li>
                        <li>Process transactions and send related information</li>
                        <li>Send administrative messages and updates</li>
                        <li>Respond to your comments and questions</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">3. Data Security</h2>
                    <p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">4. Data Sharing and Disclosure</h2>
                    <p>We do not sell your personal information. We may share your information with:</p>
                    <ul class="list-disc ml-6">
                        <li>Service providers who assist in operating our platform</li>
                        <li>Law enforcement when required by law</li>
                        <li>Third parties with your explicit consent</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">5. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul class="list-disc ml-6">
                        <li>Access your personal information</li>
                        <li>Correct inaccurate data</li>
                        <li>Request deletion of your data</li>
                        <li>Object to data processing</li>
                        <li>Export your data</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-gray-800">6. Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at privacy@herobot.id</p>
                </section>

                <section class="mt-8 text-sm text-gray-500">
                    <p>Last updated: {{ new Date('2025-05-29T03:12:38Z').toLocaleDateString() }}</p>
                </section>
            </div>
        </div>
    </AuthenticationCard>
</template> 