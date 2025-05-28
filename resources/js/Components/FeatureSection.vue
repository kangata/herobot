<template>
    <div class="overflow-hidden bg-white">
        <div class="relative mx-auto max-w-7xl py-24 px-6 sm:py-32 lg:px-8 lg:py-40">
            <svg class="absolute top-0 left-full -translate-x-1/2 -translate-y-3/4 transform lg:left-auto lg:right-full lg:translate-x-2/3 lg:translate-y-1/4"
                width="404" height="784" fill="none" viewBox="0 0 404 784" aria-hidden="true">
                <defs>
                    <pattern id="8b1b5f72-e944-4457-af67-0c6d15a99f38" x="0" y="0" width="20" height="20"
                        patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" class="text-gray-100" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="404" height="784" fill="url(#8b1b5f72-e944-4457-af67-0c6d15a99f38)" />
            </svg>

            <div class="relative lg:grid lg:grid-cols-3 lg:gap-x-12 xl:gap-x-16">
                <div class="lg:col-span-1">
                    <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-4xl">Everything You Need to Scale Your Customer Service</h2>
                </div>
                <dl class="mt-20 grid grid-cols-1 gap-16 sm:grid-cols-2 sm:gap-x-12 lg:col-span-2 lg:mt-0">
                    <div v-for="feature in features" :key="feature.name">
                        <dt>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500 text-white">
                                <component :is="feature.icon" class="h-8 w-8" aria-hidden="true" />
                            </div>
                            <p class="mt-6 text-lg font-semibold leading-8 text-gray-900">{{ feature.name }}</p>
                        </dt>
                        <dd class="mt-2 text-base text-gray-600">{{ feature.description }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ChatBubbleLeftRightIcon, CubeTransparentIcon, BoltIcon, ArrowsPointingOutIcon } from '@heroicons/vue/24/outline'

const features = [
    {
        name: 'Multi-Channel Support',
        description: 'Connect with customers where they are - WhatsApp, WhatsApp Business, Instagram, Facebook Messenger, and TikTok (coming soon). One platform to manage all your conversations.',
        icon: ChatBubbleLeftRightIcon,
    },
    {
        name: 'Smart Business Tools',
        description: 'Seamlessly integrate with your favorite tools - check shipping costs, connect to Google Forms, update spreadsheets, third party API, and more. Customize your chatbot to fit your business needs.',
        icon: CubeTransparentIcon,
    },
    {
        name: 'Instant Responses',
        description: 'Provide immediate answers to common questions, qualify leads, and handle customer inquiries 24/7. Let your team focus on high-value conversations while the bot handles the rest.',
        icon: BoltIcon,
    },
    {
        name: 'Scale With Ease',
        description: 'Start small and grow big. Whether you\'re a small business or a large enterprise, our platform grows with you. Manage multiple channels and teams from one dashboard.',
        icon: ArrowsPointingOutIcon,
    },
]
</script>
