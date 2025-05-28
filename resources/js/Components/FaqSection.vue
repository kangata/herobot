<template>
  <div class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8 lg:py-40">
      <div class="mx-auto max-w-4xl">
        <h2 class="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">Frequently asked questions</h2>
        <dl class="mt-16 divide-y divide-gray-900/10">
          <Disclosure as="div" v-for="faq in faqs" :key="faq.question" class="py-6 first:pt-0 last:pb-0" v-slot="{ open }">
            <dt>
              <DisclosureButton class="flex w-full items-start justify-between text-left text-gray-900">
                <span class="text-base/7 font-semibold">{{ faq.question }}</span>
                <span class="ml-6 flex h-7 items-center">
                  <PlusSmallIcon v-if="!open" class="size-6" aria-hidden="true" />
                  <MinusSmallIcon v-else class="size-6" aria-hidden="true" />
                </span>
              </DisclosureButton>
            </dt>
            <DisclosurePanel as="dd" class="mt-2 pr-12">
              <p class="text-base/7 text-gray-600">{{ faq.answer }}</p>
            </DisclosurePanel>
          </Disclosure>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { MinusSmallIcon, PlusSmallIcon } from '@heroicons/vue/24/outline'

const faqs = [
  {
    question: "What channels does Herobot support?",
    answer: "Herobot supports multiple messaging channels including WhatsApp, WhatsApp Business, Instagram, Facebook Messenger, and TikTok (coming soon). This allows you to manage all your customer conversations from a single platform.",
  },
  {
    question: "How much does Herobot cost?",
    answer: "Herobot is completely FREE until July 1st, 2025! Take advantage of this opportunity to scale your customer service without any cost.",
  },
  {
    question: "Can I integrate Herobot with my existing business tools?",
    answer: "Yes! Herobot seamlessly integrates with various business tools. You can check shipping costs, connect to Google Forms, update spreadsheets, and integrate with third-party APIs. The platform is highly customizable to fit your specific business needs.",
  },
  {
    question: "How does Herobot handle customer inquiries?",
    answer: "Herobot provides instant responses to common questions 24/7, qualifies leads automatically, and handles routine customer inquiries. This allows your team to focus on more complex and high-value conversations while the bot handles routine tasks.",
  },
  {
    question: "Is Herobot suitable for businesses of all sizes?",
    answer: "Absolutely! Whether you're a small business or a large enterprise, Herobot scales with your needs. You can start small and expand as your business grows. The platform allows you to manage multiple channels and teams from a single dashboard.",
  },
  {
    question: "How does the onboarding process work?",
    answer: "Our onboarding process is straightforward and user-friendly. We provide comprehensive documentation and support to help you set up your chatbot, integrate your channels, and customize your responses to match your business needs.",
  }
]
</script> 