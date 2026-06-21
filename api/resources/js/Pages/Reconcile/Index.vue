<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Pill from '@/Components/Pill.vue';
import Button from '@/Components/Button.vue';

defineProps({
    stock: { type: Array, default: () => [] },
    expiring: { type: Array, default: () => [] },
    freezeSuggestions: { type: Array, default: () => [] },
    discardCandidates: { type: Array, default: () => [] },
});

const adjustSteps = [
    { label: '¼', value: 0.25 },
    { label: '½', value: 0.5 },
    { label: '¾', value: 0.75 },
    { label: 'Full', value: 1.0 },
];

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

// Relative "in N days / N days ago" hint vs today.
function relativeHint(iso) {
    if (!iso) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const bb = new Date(iso + 'T00:00:00');
    const diffDays = Math.round((bb - today) / 86400000);
    if (diffDays === 0) return 'today';
    if (diffDays > 0) return 'in ' + diffDays + (diffDays === 1 ? ' day' : ' days');
    const past = Math.abs(diffDays);
    return past + (past === 1 ? ' day ago' : ' days ago');
}

function open(id) {
    router.post('/inventory/' + id + '/open', {}, { preserveScroll: true });
}

function adjust(id, remaining) {
    router.post('/inventory/' + id + '/adjust', { remaining }, { preserveScroll: true });
}

function freeze(id) {
    router.post('/inventory/' + id + '/freeze', {}, { preserveScroll: true });
}

function discard(id) {
    router.post('/inventory/' + id + '/discard', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Weekly Reconcile" />
    <AppLayout>
        <PageHeader
            title="Weekly Reconcile"
            subtitle="Confirm what's left, freeze what'll spoil, toss what's gone."
        />

        <div class="flex flex-col gap-4">
            <!-- 1. Confirm stock -->
            <Card>
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-pill bg-terracotta text-sm font-semibold text-white">1</span>
                    <h2 class="font-serif text-lg font-semibold text-ink">Confirm stock</h2>
                </div>

                <ul v-if="stock.length" class="flex flex-col divide-y divide-hairline">
                    <li v-for="lot in stock" :key="lot.id" class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-ink">{{ lot.name }}</span>
                            <div class="flex items-center gap-2">
                                <Pill tone="neutral">{{ capitalize(lot.location) }}</Pill>
                                <Pill v-if="lot.is_opened" tone="neutral">Opened</Pill>
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

                        <!-- Actions -->
                        <div class="mt-3 flex flex-wrap items-center gap-2">
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
                            <Button
                                v-if="!lot.is_opened"
                                variant="ghost"
                                class="!px-3 !py-1.5"
                                @click="open(lot.id)"
                            >
                                Mark opened
                            </Button>
                        </div>
                    </li>
                </ul>
                <p v-else class="py-4 text-sm text-muted">Nothing in stock yet.</p>
            </Card>

            <!-- 2. Expiring soon -->
            <Card>
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-pill bg-terracotta text-sm font-semibold text-white">2</span>
                    <h2 class="font-serif text-lg font-semibold text-ink">Expiring soon</h2>
                </div>

                <ul v-if="expiring.length" class="flex flex-col divide-y divide-hairline">
                    <li v-for="lot in expiring" :key="lot.id" class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-ink">{{ lot.name }}</span>
                            <Pill tone="neutral">{{ capitalize(lot.location) }}</Pill>
                        </div>
                        <span class="text-xs font-semibold text-bestbefore">
                            Best before {{ formatDate(lot.effective_best_before) }}
                            <span class="font-medium text-subtle">· {{ relativeHint(lot.effective_best_before) }}</span>
                        </span>
                    </li>
                </ul>
                <p v-else class="py-4 text-sm text-muted">Nothing expiring soon ✓</p>
            </Card>

            <!-- 3. Freeze to save -->
            <Card>
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-pill bg-terracotta text-sm font-semibold text-white">3</span>
                    <h2 class="font-serif text-lg font-semibold text-ink">Freeze to save</h2>
                </div>

                <ul v-if="freezeSuggestions.length" class="flex flex-col divide-y divide-hairline">
                    <li v-for="lot in freezeSuggestions" :key="lot.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-ink">{{ lot.name }}</span>
                                <Pill tone="neutral">{{ capitalize(lot.location) }}</Pill>
                            </div>
                            <span class="text-xs font-semibold text-bestbefore">
                                Best before {{ formatDate(lot.effective_best_before) }}
                                <span class="font-medium text-subtle">· {{ relativeHint(lot.effective_best_before) }}</span>
                            </span>
                        </div>
                        <Button variant="primary" class="!px-3 !py-1.5" @click="freeze(lot.id)">Freeze</Button>
                    </li>
                </ul>
                <p v-else class="py-4 text-sm text-muted">No freeze suggestions.</p>
            </Card>

            <!-- 4. Worth checking -->
            <Card>
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-pill bg-terracotta text-sm font-semibold text-white">4</span>
                    <h2 class="font-serif text-lg font-semibold text-ink">Worth checking</h2>
                </div>
                <p class="mb-3 text-sm text-muted">
                    These are past their estimated date — <em>you</em> decide; nothing is removed automatically.
                </p>

                <ul v-if="discardCandidates.length" class="flex flex-col divide-y divide-hairline">
                    <li v-for="lot in discardCandidates" :key="lot.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-ink">{{ lot.name }}</span>
                                <Pill tone="neutral">{{ capitalize(lot.location) }}</Pill>
                            </div>
                            <span class="text-xs font-semibold text-bestbefore">
                                Best before {{ formatDate(lot.effective_best_before) }}
                                <span class="font-medium text-subtle">· {{ relativeHint(lot.effective_best_before) }}</span>
                            </span>
                        </div>
                        <Button variant="danger" class="!px-3 !py-1.5" @click="discard(lot.id)">Discard</Button>
                    </li>
                </ul>
                <p v-else class="py-4 text-sm text-muted">Nothing to toss ✓</p>
            </Card>
        </div>
    </AppLayout>
</template>
