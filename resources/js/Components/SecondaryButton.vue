<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  type: {
    type: String,
    default: 'button',
  },
  href: {
    type: String,
    default: null,
  },
});

const buttonClass = 'inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition';
</script>

<template>
  <template v-if="href">
    <Link :href="href" :class="buttonClass">
      <slot />
    </Link>
  </template>
  <template v-else>
    <button :type="type" :class="buttonClass">
      <slot />
    </button>
  </template>
</template>

