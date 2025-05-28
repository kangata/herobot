<script setup>
import { Head } from '@inertiajs/vue3';
import HeroSection from '../Components/HeroSection.vue';
import FeatureSection from '../Components/FeatureSection.vue';
import FaqSection from '../Components/FaqSection.vue';
import FooterSection from '../Components/FooterSection.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Herobot - Your 24/7 Customer Service Assistant | Multi-Channel Support" />

    <HeroSection />

    <FeatureSection />

    <FaqSection />

    <FooterSection />
</template>
