<script setup>
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
  type: {
    type: String,
    default: 'submit',
  },
  href: {
    type: String,
    default: null,
  },
});

const buttonClass = 'inline-flex justify-center rounded-lg text-sm font-semibold py-2.5 px-4 bg-indigo-600 text-white hover:bg-indigo-500';
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
