<script setup>
defineProps({
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <div
        :class="[
            'rounded-card border border-hairline bg-card',
            padded ? 'p-5' : '',
        ]"
    >
        <slot />
    </div>
</template>
