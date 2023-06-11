<script setup>
import { Head } from '@inertiajs/vue3';
import HeroSection from '../Components/HeroSection.vue';
import FeatureSection from '../Components/FeatureSection.vue';
import NewsletterSection from '../Components/NewsletterSection.vue';
import FooterSection from '../Components/FooterSection.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Herobot - Chatbot untuk Bisnis" />

    <HeroSection />

    <FeatureSection />

    <NewsletterSection />

    <FooterSection />
</template>
