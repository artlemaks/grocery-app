<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { pollAiJob } from '@/composables/useAiJob';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    days: { type: Array, default: () => [] },
    slots: { type: Array, default: () => [] },
    entries: { type: Array, default: () => [] },
    recipes: { type: Array, default: () => [] },
});

// Local UI state: which cell is currently "adding", keyed by `${slotId}-${day}`.
const addingKey = ref(null);
const chosenRecipe = ref('');

function cellKey(slot, day) {
    return slot.id + '-' + day;
}

function entriesFor(slot, day) {
    return props.entries.filter(
        (e) => e.slot_tag_id === slot.id && e.date === day,
    );
}

function weekday(iso) {
    return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short' });
}

function dayNumber(iso) {
    return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
}

function generate() {
    router.post('/planner/' + props.plan.id + '/generate');
}

function openAdd(slot, day) {
    addingKey.value = cellKey(slot, day);
    chosenRecipe.value = '';
}

function closeAdd() {
    addingKey.value = null;
    chosenRecipe.value = '';
}

function addEntry(slot, day) {
    if (!chosenRecipe.value) return;
    router.post(
        '/planner/' + props.plan.id + '/entries',
        { date: day, slot_tag_id: slot.id, recipe_id: chosenRecipe.value },
        { preserveScroll: true, onSuccess: closeAdd },
    );
}

function removeEntry(entry) {
    router.delete('/planner/' + props.plan.id + '/entries/' + entry.id, { preserveScroll: true });
}

// --- AI meal suggestions ---
const suggestions = ref([]);
const suggesting = ref(false);
const suggestError = ref('');

function suggest() {
    suggestError.value = '';
    suggestions.value = [];
    router.post('/planner/' + props.plan.id + '/suggest', {}, { preserveScroll: true });
}

const page = usePage();
watch(
    () => page.props.flash?.aiJob,
    (job) => {
        if (!job || job.kind !== 'suggest') return;
        suggesting.value = true;
        pollAiJob(job.id)
            .then((data) => {
                suggesting.value = false;
                suggestions.value = data.result?.suggestions ?? [];
            })
            .catch((e) => {
                suggesting.value = false;
                suggestError.value = e.message;
            });
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Weekly Planner" />
    <AppLayout>
        <PageHeader title="Weekly Planner" :subtitle="'Week of ' + plan.week_start_date">
            <template #actions>
                <Button variant="ghost" :disabled="suggesting" @click="suggest">
                    {{ suggesting ? 'Thinking…' : '✨ What should we have?' }}
                </Button>
                <Button @click="generate">Generate shopping list</Button>
            </template>
        </PageHeader>

        <!-- AI suggestions -->
        <Card v-if="suggestions.length || suggesting || suggestError" class="mb-6">
            <div class="mb-3 font-serif text-lg font-semibold text-ink">✨ Suggestions</div>
            <p v-if="suggesting" class="text-sm text-muted">Thinking about what to cook, using what you have…</p>
            <p v-else-if="suggestError" class="text-sm text-meat">{{ suggestError }}</p>
            <ul v-else class="space-y-2">
                <li
                    v-for="(s, i) in suggestions"
                    :key="i"
                    class="flex items-start gap-3 rounded-card border border-hairline bg-paper p-3"
                >
                    <Pill :tone="s.uses_stock ? 'veg' : 'neutral'">{{ s.uses_stock ? 'uses stock' : 'shop' }}</Pill>
                    <div>
                        <div class="font-semibold text-ink">{{ s.recipe_name }}</div>
                        <div class="text-sm text-muted">{{ s.reason }}</div>
                    </div>
                </li>
            </ul>
        </Card>

        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] border-collapse">
                    <thead>
                        <tr>
                            <th class="w-28 border-b border-hairline px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-label">
                                Meal
                            </th>
                            <th
                                v-for="day in days"
                                :key="day"
                                class="border-b border-l border-hairline px-3 py-3 text-left"
                            >
                                <div class="text-xs font-semibold uppercase tracking-wide text-label">{{ weekday(day) }}</div>
                                <div class="text-sm text-muted">{{ dayNumber(day) }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="slot in slots" :key="slot.id" class="align-top">
                            <th class="border-b border-hairline px-4 py-3 text-left font-serif text-base font-semibold text-ink">
                                {{ slot.name }}
                            </th>
                            <td
                                v-for="day in days"
                                :key="day"
                                class="border-b border-l border-hairline px-2 py-2"
                            >
                                <div class="flex flex-col gap-2">
                                    <div
                                        v-for="entry in entriesFor(slot, day)"
                                        :key="entry.id"
                                        class="rounded-card border border-hairline bg-paper px-3 py-2"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-medium text-ink">{{ entry.recipe?.name }}</span>
                                            <button
                                                type="button"
                                                class="-mr-1 -mt-0.5 shrink-0 rounded-full px-1.5 text-muted transition hover:text-meat-strong"
                                                :aria-label="'Remove ' + entry.recipe?.name"
                                                @click="removeEntry(entry)"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                        <div class="mt-1.5">
                                            <Pill v-if="entry.is_split" tone="meat">2 versions</Pill>
                                            <Pill v-else tone="veg">Shared</Pill>
                                        </div>
                                    </div>

                                    <!-- Add control -->
                                    <div v-if="addingKey === cellKey(slot, day)" class="flex flex-col gap-1.5">
                                        <select
                                            v-model="chosenRecipe"
                                            class="w-full rounded-[10px] border border-hairline bg-paper px-2 py-1.5 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                                            @change="addEntry(slot, day)"
                                        >
                                            <option value="" disabled>Choose recipe…</option>
                                            <option v-for="r in recipes" :key="r.id" :value="r.id">{{ r.name }}</option>
                                        </select>
                                        <button
                                            type="button"
                                            class="self-start text-xs text-subtle hover:text-ink"
                                            @click="closeAdd"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                    <button
                                        v-else
                                        type="button"
                                        class="rounded-card border border-dashed border-hairline px-3 py-1.5 text-left text-xs font-medium text-subtle transition hover:border-terracotta hover:text-terracotta"
                                        @click="openAdd(slot, day)"
                                    >
                                        + add
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
