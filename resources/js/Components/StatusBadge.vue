<template>
  <span
    :class="[
      'px-2 py-1 text-xs font-medium rounded-full',
      status === 'completed' && 'bg-green-100 text-green-800',
      status === 'pending' && 'bg-yellow-100 text-yellow-800',
      status === 'indexing' && 'bg-blue-100 text-blue-800',
      status === 'failed' && 'bg-red-100 text-red-800',
    ]"
  >
    {{ status.charAt(0).toUpperCase() + status.slice(1) }}
  </span>
</template>

<script setup>
defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['pending', 'indexing', 'completed', 'failed'].includes(value),
  },
});
</script> 