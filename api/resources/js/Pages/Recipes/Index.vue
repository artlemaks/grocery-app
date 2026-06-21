<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';
import TextInput from '@/Components/TextInput.vue';
import { pollAiJob } from '@/composables/useAiJob';

defineProps({
    recipes: { type: Array, default: () => [] },
});

const form = useForm({ name: '' });
const importForm = useForm({ url: '' });
const importing = ref(false);
const importError = ref('');

function create() {
    form.post('/recipes'); // server redirects to the editor on success
}

function remove(r) {
    router.delete('/recipes/' + r.id, { preserveScroll: true });
}

function importUrl() {
    importError.value = '';
    importForm.post('/recipes/import-url', { preserveScroll: true });
}

// When the server flashes an import job, poll it and open the resulting draft.
const page = usePage();
watch(
    () => page.props.flash?.aiJob,
    (job) => {
        if (!job || job.kind !== 'import') return;
        importing.value = true;
        pollAiJob(job.id)
            .then((data) => {
                importing.value = false;
                importForm.reset();
                if (data.result?.recipe_id) {
                    router.visit('/recipes/' + data.result.recipe_id + '/edit');
                }
            })
            .catch((e) => {
                importing.value = false;
                importError.value = e.message;
            });
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Recipes" />
    <AppLayout>
        <PageHeader title="Recipes">
            <template #actions>
                <form class="flex items-end gap-2" @submit.prevent="create">
                    <TextInput
                        v-model="form.name"
                        label="New recipe"
                        placeholder="Recipe name"
                        :error="form.errors.name"
                    />
                    <Button type="submit" :disabled="form.processing">New recipe</Button>
                </form>
            </template>
        </PageHeader>

        <!-- AI: import from a URL -->
        <Card class="mb-6">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="importUrl">
                <div class="flex-1 min-w-64">
                    <TextInput
                        v-model="importForm.url"
                        label="✨ Import a recipe from a URL"
                        type="url"
                        placeholder="https://…/some-recipe"
                        :error="importForm.errors.url || importError"
                    />
                </div>
                <Button type="submit" variant="primary" :disabled="importForm.processing || importing">
                    {{ importing ? 'Importing…' : 'Import' }}
                </Button>
                <p class="w-full text-xs text-subtle">
                    We try the page's structured data first, then AI — the result lands as a draft for you to review.
                </p>
            </form>
        </Card>

        <!-- Grid -->
        <div v-if="recipes.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="r in recipes" :key="r.id" class="flex flex-col">
                <div class="flex items-start justify-between gap-2">
                    <div class="font-serif text-xl font-semibold text-ink">{{ r.name }}</div>
                    <Pill v-if="r.is_draft" tone="meat">Draft · review</Pill>
                </div>

                <div v-if="r.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
                    <Pill v-for="tag in r.tags" :key="tag.id" tone="neutral">{{ tag.name }}</Pill>
                </div>

                <div class="mt-3 text-sm text-muted">
                    {{ r.ingredient_count }} ingredients
                    <span v-if="r.servings_default"> · serves {{ r.servings_default }}</span>
                </div>

                <div class="mt-auto flex items-center gap-2 pt-5">
                    <Link :href="'/recipes/' + r.id + '/edit'">
                        <Button variant="ghost">{{ r.is_draft ? 'Review' : 'Edit' }}</Button>
                    </Link>
                    <Button variant="danger" @click="remove(r)">Delete</Button>
                </div>
            </Card>
        </div>

        <!-- Empty state -->
        <Card v-else>
            <div class="py-8 text-center">
                <div class="font-serif text-xl text-ink">No recipes yet</div>
                <p class="mt-1.5 text-sm text-muted">
                    Name a recipe above, or import one from a URL, and we'll open the editor.
                </p>
            </div>
        </Card>
    </AppLayout>
</template>
