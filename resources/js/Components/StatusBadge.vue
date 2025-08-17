<template>
  <span
    class="px-2 py-1 text-xs font-medium rounded-full"
    :class="statusClasses[status]"
  >
    {{ capitalize(status) }}
  </span>
</template>

<script setup>
const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['pending', 'indexing', 'completed', 'failed', 'active', 'inactive'].includes(value),
  },
});

const statusClasses = {
  completed: 'bg-green-100 text-green-800',
  active: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  inactive: 'bg-yellow-100 text-yellow-800',
  indexing: 'bg-blue-100 text-blue-800',
  failed: 'bg-red-100 text-red-800',
};

const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);
</script>