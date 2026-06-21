<script setup>
defineProps({
    tone: { type: String, default: 'neutral' }, // veg | meat | frozen | neutral
});

const tones = {
    veg: 'bg-veg-soft text-veg-strong',
    meat: 'bg-meat-soft text-meat-strong',
    frozen: 'bg-frozen-soft text-frozen-strong',
    neutral: 'bg-paper text-muted',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-xs font-semibold',
            tones[tone],
        ]"
    >
        <slot />
    </span>
</template>
