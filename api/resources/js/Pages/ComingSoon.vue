<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';

defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <PageHeader :title="title" subtitle="Coming in Phase 1b-ii." />
        <Card>
            <div class="py-10 text-center text-sm text-muted">
                This screen isn't built yet — the backend endpoints exist, the UI lands in the next pass.
            </div>
        </Card>
    </AppLayout>
</template>
