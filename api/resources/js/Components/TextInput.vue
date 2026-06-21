<script setup>
defineProps({
    label: String,
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    error: String,
    placeholder: String,
});
defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
        />
        <span v-if="error" class="mt-1 block text-xs text-meat-strong">{{ error }}</span>
    </label>
</template>
