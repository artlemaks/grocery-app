<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';

defineProps({
    stats: { type: Object, required: true },
});

const user = computed(() => usePage().props.auth?.user);

const tiles = [
    { href: '/recipes', label: 'Recipes', key: 'recipes' },
    { href: '/ingredients', label: 'Ingredients', key: 'ingredients' },
    { href: '/inventory', label: 'Active inventory', key: 'inventory' },
];
</script>

<template>
    <Head title="Home" />
    <AppLayout>
        <PageHeader :title="`Welcome back, ${user?.name}`" subtitle="Plan the week, shop smart, waste less." />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Link v-for="tile in tiles" :key="tile.key" :href="tile.href">
                <Card class="transition hover:border-terracotta/40">
                    <div class="text-xs font-semibold uppercase tracking-wide text-label">{{ tile.label }}</div>
                    <div class="mt-2 font-serif text-4xl font-semibold text-ink">{{ stats[tile.key] }}</div>
                </Card>
            </Link>
        </div>

        <Card class="mt-6">
            <div class="text-sm text-muted">
                This is the Larder web client (Phase 1b-i). The <strong class="text-ink">Recipes</strong> and
                <strong class="text-ink">Ingredients</strong> screens are live. The weekly planner, shopping list,
                and inventory screens arrive in Phase 1b-ii.
            </div>
        </Card>
    </AppLayout>
</template>
