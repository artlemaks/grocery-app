<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: String,
});
</script>

<template>
    <div class="mb-7 flex items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-semibold tracking-tight text-ink">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1.5 max-w-xl text-sm text-muted">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
