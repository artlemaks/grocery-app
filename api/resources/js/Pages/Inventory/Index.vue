<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Pill from '@/Components/Pill.vue';
import Button from '@/Components/Button.vue';

defineProps({
    groups: { type: Array, default: () => [] },
});

const adjustSteps = [
    { label: '¼', value: 0.25 },
    { label: '½', value: 0.5 },
    { label: '¾', value: 0.75 },
    { label: 'Full', value: 1.0 },
];

function open(id) {
    router.post('/inventory/' + id + '/open', {}, { preserveScroll: true });
}

function adjust(id, remaining) {
    router.post('/inventory/' + id + '/adjust', { remaining }, { preserveScroll: true });
}

function freeze(id) {
    router.post('/inventory/' + id + '/freeze', {}, { preserveScroll: true });
}

function thaw(id) {
    router.post('/inventory/' + id + '/thaw', {}, { preserveScroll: true });
}

function discard(id) {
    router.post('/inventory/' + id + '/discard', {}, { preserveScroll: true });
}

function capitalize(s) {
    if (!s) return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function pct(remaining) {
    const v = Math.max(0, Math.min(1, Number(remaining) || 0));
    return Math.round(v * 100);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

// True when best-before is null/within ~3 days/past -> highlight as expiring.
function isExpiring(iso) {
    if (!iso) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const bb = new Date(iso + 'T00:00:00');
    const diffDays = Math.floor((bb - today) / 86400000);
    return diffDays <= 3;
}
</script>

<template>
    <Head title="Inventory" />
    <AppLayout>
        <PageHeader title="Inventory" subtitle="What's in the kitchen right now." />

        <div v-if="groups.length" class="flex flex-col gap-4">
            <Card v-for="group in groups" :key="group.location">
                <h2 class="mb-3 font-serif text-lg font-semibold text-ink">{{ capitalize(group.location) }}</h2>
                <ul class="flex flex-col divide-y divide-hairline">
                    <li v-for="lot in group.items" :key="lot.id" class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-ink">{{ lot.name }}</span>
                            <div class="flex items-center gap-2">
                                <Pill v-if="lot.is_opened" tone="neutral">Opened</Pill>
                                <Pill v-if="isExpiring(lot.effective_best_before)" tone="frozen">Use soon</Pill>
                            </div>
                        </div>

                        <!-- Remaining bar -->
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-pill bg-paper">
                                <div
                                    class="h-full rounded-pill bg-terracotta"
                                    :style="{ width: pct(lot.remaining) + '%' }"
                                ></div>
                            </div>
                            <span class="w-10 shrink-0 text-right text-xs font-semibold text-muted">{{ pct(lot.remaining) }}%</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                            <span
                                :class="[
                                    'font-medium',
                                    isExpiring(lot.effective_best_before) ? 'text-bestbefore' : 'text-subtle',
                                ]"
                            >
                                Best before {{ formatDate(lot.effective_best_before) }}
                            </span>
                            <span v-if="lot.status" class="capitalize text-subtle">{{ lot.status }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <Button
                                v-if="lot.status === 'active' && !lot.is_opened"
                                variant="ghost"
                                class="!px-3 !py-1.5"
                                @click="open(lot.id)"
                            >
                                Open
                            </Button>

                            <div class="inline-flex items-center gap-1 rounded-pill border border-hairline bg-card px-1 py-0.5">
                                <span class="px-1.5 text-xs font-semibold text-label">Adjust</span>
                                <button
                                    v-for="step in adjustSteps"
                                    :key="step.value"
                                    type="button"
                                    class="rounded-pill px-2 py-1 text-xs font-semibold text-ink transition hover:bg-paper"
                                    @click="adjust(lot.id, step.value)"
                                >
                                    {{ step.label }}
                                </button>
                            </div>

                            <Pill v-if="lot.status === 'frozen'" tone="frozen">Frozen</Pill>

                            <Button
                                v-if="lot.status === 'active'"
                                variant="ghost"
                                class="!px-3 !py-1.5"
                                @click="freeze(lot.id)"
                            >
                                Freeze
                            </Button>
                            <Button
                                v-else-if="lot.status === 'frozen'"
                                variant="ghost"
                                class="!px-3 !py-1.5"
                                @click="thaw(lot.id)"
                            >
                                Thaw
                            </Button>

                            <Button
                                variant="danger"
                                class="!px-3 !py-1.5"
                                @click="discard(lot.id)"
                            >
                                Discard
                            </Button>
                        </div>
                    </li>
                </ul>
            </Card>
        </div>

        <!-- Empty state -->
        <Card v-else>
            <div class="py-8 text-center">
                <div class="font-serif text-xl text-ink">Kitchen's empty</div>
                <p class="mt-1.5 text-sm text-muted">
                    Nothing in the larder yet. Complete a shopping list to stock up.
                </p>
            </div>
        </Card>
    </AppLayout>
</template>
