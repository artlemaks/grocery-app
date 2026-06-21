<script setup>
defineProps({
    variant: { type: String, default: 'primary' }, // primary | ghost | danger
    type: { type: String, default: 'button' },
});

const variants = {
    primary: 'bg-terracotta text-white shadow-[0_10px_24px_-10px_rgba(188,91,60,.7)] hover:brightness-105',
    ghost: 'bg-card text-ink border border-hairline hover:bg-paper',
    danger: 'bg-meat-soft text-meat-strong hover:brightness-95',
};
</script>

<template>
    <button
        :type="type"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-[12px] px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed',
            variants[variant],
        ]"
    >
        <slot />
    </button>
</template>
