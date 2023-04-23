<template>
    <button class="btn" :class="[variantClass, sizeClass, colorClass, fullWidthClass, disabledClass]" :type="type"
        @click="emit('click', $event)" :disabled="disabled" :aria-label="ariaLabel">
        <slot></slot>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'contained',
    },
    color: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary'].includes(value),
    },
    size: {
        type: String,
        default: 'medium',
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
    ariaLabel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['click']);

const variantClass = computed(() => {
    return {
        contained: 'bg-blue-600 hover:bg-blue-700',
        outlined: 'border-2 border-blue-600 text-blue-600 hover:bg-blue-100',
        text: 'text-blue-600 hover:bg-blue-100',
    }[props.variant];
});

const sizeClass = computed(() => {
    return {
        small: 'px-2 py-1 text-xs',
        medium: 'px-4 py-2 text-sm',
        large: 'px-6 py-3 text-base',
    }[props.size];
});

const colorClass = computed(() => {
    return {
        primary: 'text-white',
        secondary: 'text-black',
    }[props.color];
});

const fullWidthClass = computed(() => {
    return props.fullWidth ? 'w-full' : '';
});

const disabledClass = computed(() => {
    return props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer';
});
</script>

<style scoped>
.btn {
    border-radius: 4px;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, color 0.2s ease-in-out;
}
</style>
