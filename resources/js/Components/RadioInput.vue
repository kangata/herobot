<script setup>
defineProps({
    modelValue: String,
    options: Array,
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="mt-2 space-y-4">
        <div class="flex items-center" v-for="option in options">
            <input :id="option.value" name="notification-method" type="radio"
                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600"
                :checked="modelValue === option.value"
                @input="$emit('update:modelValue', option.value)"
            >
            <label :for="option.value" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ option.label }}</label>
        </div>
    </div>
</template>
