<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';

defineProps({
    items: { type: Array, default: () => [] },
});

const amounts = [
    { label: '¼', value: 0.25 },
    { label: '⅓', value: 0.33 },
    { label: '½', value: 0.5 },
    { label: '¾', value: 0.75 },
    { label: 'All', value: 1 },
];

function pct(remaining) {
    const v = Math.max(0, Math.min(1, Number(remaining) || 0));
    return Math.round(v * 100);
}

function logUsage(item, amount) {
    router.post('/cook/' + item.id + '/usage', { amount }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Cook / Log Usage" />
    <AppLayout>
        <PageHeader title="Cook / Log Usage" subtitle="Tap how much you used." />

        <div v-if="items.length" class="flex flex-col gap-4">
            <Card v-for="item in items" :key="item.id">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="font-serif text-lg font-semibold text-ink">{{ item.name }}</div>
                        <div class="mt-1 flex items-center gap-2 text-xs text-subtle">
                            <span class="capitalize">{{ item.location }}</span>
                            <span v-if="item.is_opened" aria-hidden="true">·</span>
                            <span v-if="item.is_opened">Opened</span>
                        </div>
                        <!-- Remaining bar -->
                        <div class="mt-2 flex items-center gap-2 sm:max-w-[220px]">
                            <div class="h-2 flex-1 overflow-hidden rounded-pill bg-paper">
                                <div
                                    class="h-full rounded-pill bg-terracotta"
                                    :style="{ width: pct(item.remaining) + '%' }"
                                ></div>
                            </div>
                            <span class="w-12 shrink-0 text-right text-xs font-semibold text-muted">{{ pct(item.remaining) }}% left</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="a in amounts"
                            :key="a.value"
                            variant="ghost"
                            @click="logUsage(item, a.value)"
                        >
                            {{ a.label }}
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Empty state -->
        <Card v-else>
            <div class="py-8 text-center">
                <div class="font-serif text-xl text-ink">Nothing to cook with yet</div>
                <p class="mt-1.5 text-sm text-muted">
                    Complete a shopping list to stock the kitchen, then log what you use here.
                </p>
            </div>
        </Card>
    </AppLayout>
</template>
