<script setup>
import Pill from './Pill.vue';

const props = defineProps({
    dietClass: { type: String, required: true }, // meat | fish | dairy | egg | plant | other
});

// Map an ingredient's diet_class to a tone + label (ADR-0002 colour system).
const map = {
    meat: { tone: 'meat', label: 'Meat' },
    fish: { tone: 'frozen', label: 'Fish' },
    dairy: { tone: 'neutral', label: 'Dairy' },
    egg: { tone: 'neutral', label: 'Egg' },
    plant: { tone: 'veg', label: 'Veg' },
    other: { tone: 'neutral', label: 'Other' },
};
</script>

<template>
    <Pill :tone="(map[dietClass] ?? map.other).tone">
        {{ (map[dietClass] ?? map.other).label }}
    </Pill>
</template>
