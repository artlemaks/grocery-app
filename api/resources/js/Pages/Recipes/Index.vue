<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    recipes: { type: Array, default: () => [] },
});

const form = useForm({ name: '' });

function create() {
    // Server redirects to the editor on success.
    form.post('/recipes');
}

function remove(r) {
    router.delete('/recipes/' + r.id, { preserveScroll: true });
}
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

        <!-- Grid -->
        <div v-if="recipes.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="r in recipes" :key="r.id" class="flex flex-col">
                <div class="font-serif text-xl font-semibold text-ink">{{ r.name }}</div>

                <div v-if="r.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
                    <Pill v-for="tag in r.tags" :key="tag.id" tone="neutral">{{ tag.name }}</Pill>
                </div>

                <div class="mt-3 text-sm text-muted">
                    {{ r.ingredient_count }} ingredients
                    <span v-if="r.servings_default"> · serves {{ r.servings_default }}</span>
                </div>

                <div class="mt-auto flex items-center gap-2 pt-5">
                    <Link :href="'/recipes/' + r.id + '/edit'">
                        <Button variant="ghost">Edit</Button>
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
                    Name a recipe above and we'll open the editor so you can add ingredients.
                </p>
            </div>
        </Card>
    </AppLayout>
</template>
