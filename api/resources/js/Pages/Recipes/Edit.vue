<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';
import DietPill from '@/Components/DietPill.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    recipe: { type: Object, required: true },
    lines: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    subRecipes: { type: Array, default: () => [] },
    expanded: { type: Array, default: () => [] },
    allIngredients: { type: Array, default: () => [] },
    allTags: { type: Array, default: () => [] },
    allRecipes: { type: Array, default: () => [] },
});

// --- Details ---
const detailsForm = useForm({
    name: props.recipe.name,
    servings_default: props.recipe.servings_default,
    instructions: props.recipe.instructions,
});

function saveDetails() {
    detailsForm.put('/recipes/' + props.recipe.id, { preserveScroll: true });
}

// --- Ingredient lines ---
const newLine = ref({ ingredient_id: '', quantity_hint: '' });

function addLine() {
    if (!newLine.value.ingredient_id) return;
    router.post(
        '/recipes/' + props.recipe.id + '/ingredients',
        {
            ingredient_id: newLine.value.ingredient_id,
            quantity_hint: newLine.value.quantity_hint,
            is_optional: false,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                newLine.value = { ingredient_id: '', quantity_hint: '' };
            },
        },
    );
}

function removeLine(line) {
    router.delete('/recipes/' + props.recipe.id + '/ingredients/' + line.id, {
        preserveScroll: true,
    });
}

// --- Tags ---
const newTagId = ref('');

const availableTags = computed(() => {
    const attached = new Set(props.tags.map((t) => t.id));
    return props.allTags.filter((t) => !attached.has(t.id));
});

function addTag() {
    if (!newTagId.value) return;
    router.post(
        '/recipes/' + props.recipe.id + '/tags',
        { tag_id: newTagId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newTagId.value = '';
            },
        },
    );
}

function removeTag(tag) {
    router.delete('/recipes/' + props.recipe.id + '/tags/' + tag.id, {
        preserveScroll: true,
    });
}

// --- Sub-recipes ---
const componentForm = useForm({ child_recipe_id: '' });

const availableRecipes = computed(() => {
    const used = new Set(props.subRecipes.map((s) => s.id));
    return props.allRecipes.filter(
        (r) => r.id !== props.recipe.id && !used.has(r.id),
    );
});

function addComponent() {
    if (!componentForm.child_recipe_id) return;
    componentForm.post('/recipes/' + props.recipe.id + '/components', {
        preserveScroll: true,
        onSuccess: () => componentForm.reset(),
    });
}

function removeComponent(child) {
    router.delete('/recipes/' + props.recipe.id + '/components/' + child.id, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="recipe.name" />
    <AppLayout>
        <PageHeader :title="recipe.name" subtitle="Edit recipe">
            <template #actions>
                <Link href="/recipes">
                    <Button variant="ghost">Back to recipes</Button>
                </Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Details -->
            <Card class="lg:col-span-2">
                <div class="mb-4 font-serif text-lg font-semibold text-ink">Details</div>
                <form class="space-y-4" @submit.prevent="saveDetails">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <TextInput
                                v-model="detailsForm.name"
                                label="Name"
                                :error="detailsForm.errors.name"
                            />
                        </div>
                        <TextInput
                            v-model="detailsForm.servings_default"
                            label="Servings"
                            type="number"
                            :error="detailsForm.errors.servings_default"
                        />
                    </div>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Method</span>
                        <textarea
                            v-model="detailsForm.instructions"
                            rows="5"
                            placeholder="Optional cooking instructions…"
                            class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm leading-relaxed text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                        ></textarea>
                        <span v-if="detailsForm.errors.instructions" class="mt-1 block text-xs text-meat-strong">{{ detailsForm.errors.instructions }}</span>
                    </label>

                    <div>
                        <Button type="submit" :disabled="detailsForm.processing">Save</Button>
                    </div>
                </form>
            </Card>

            <!-- Ingredients -->
            <Card>
                <div class="mb-4 flex items-center justify-between">
                    <div class="font-serif text-lg font-semibold text-ink">Ingredients</div>
                    <span class="text-xs text-subtle">name only — quantities optional</span>
                </div>

                <ul v-if="lines.length" class="space-y-2">
                    <li
                        v-for="line in lines"
                        :key="line.id"
                        class="flex items-center gap-3 rounded-[10px] border border-hairline bg-paper px-3 py-2.5"
                    >
                        <span class="font-medium text-ink">{{ line.name }}</span>
                        <DietPill :dietClass="line.diet_class" />
                        <span v-if="line.quantity_hint" class="text-xs text-muted">· {{ line.quantity_hint }}</span>
                        <Pill v-if="line.is_optional" tone="neutral">optional</Pill>
                        <button
                            type="button"
                            class="ml-auto text-meat-strong hover:brightness-90"
                            :aria-label="'Remove ' + line.name"
                            @click="removeLine(line)"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">No ingredients yet.</p>

                <!-- Add line -->
                <form class="mt-4 flex flex-col gap-2 border-t border-hairline pt-4 sm:flex-row sm:items-end" @submit.prevent="addLine">
                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Ingredient</span>
                        <select
                            v-model="newLine.ingredient_id"
                            class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                        >
                            <option value="">Choose…</option>
                            <option v-for="ing in allIngredients" :key="ing.id" :value="ing.id">{{ ing.name }}</option>
                        </select>
                    </label>
                    <div class="w-full sm:w-40">
                        <TextInput
                            v-model="newLine.quantity_hint"
                            label="Quantity"
                            placeholder="1 pack…"
                        />
                    </div>
                    <Button type="submit">Add</Button>
                </form>
            </Card>

            <!-- Tags -->
            <Card>
                <div class="mb-4 font-serif text-lg font-semibold text-ink">Tags</div>

                <div v-if="tags.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="tag in tags"
                        :key="tag.id"
                        class="inline-flex items-center gap-1.5 rounded-pill bg-paper px-2.5 py-0.5 text-xs font-semibold text-muted"
                    >
                        {{ tag.name }}
                        <button
                            type="button"
                            class="text-subtle hover:text-meat-strong"
                            :aria-label="'Remove tag ' + tag.name"
                            @click="removeTag(tag)"
                        >×</button>
                    </span>
                </div>
                <p v-else class="text-sm text-muted">No tags attached.</p>

                <form class="mt-4 flex items-end gap-2 border-t border-hairline pt-4" @submit.prevent="addTag">
                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Add tag</span>
                        <select
                            v-model="newTagId"
                            :disabled="!availableTags.length"
                            class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20 disabled:opacity-50"
                        >
                            <option value="">{{ availableTags.length ? 'Choose…' : 'No more tags' }}</option>
                            <option v-for="tag in availableTags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
                        </select>
                    </label>
                    <Button type="submit" :disabled="!newTagId">Add</Button>
                </form>
            </Card>

            <!-- Sub-recipes -->
            <Card>
                <div class="mb-4 flex items-center justify-between">
                    <div class="font-serif text-lg font-semibold text-ink">Sub-recipes</div>
                    <span class="text-xs text-subtle">expanded into the shopping list</span>
                </div>

                <ul v-if="subRecipes.length" class="space-y-2">
                    <li
                        v-for="child in subRecipes"
                        :key="child.id"
                        class="flex items-center gap-3 rounded-[10px] border border-hairline bg-paper px-3 py-2.5"
                    >
                        <span class="font-medium text-ink">{{ child.name }}</span>
                        <button
                            type="button"
                            class="ml-auto text-meat-strong hover:brightness-90"
                            :aria-label="'Remove sub-recipe ' + child.name"
                            @click="removeComponent(child)"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">No sub-recipes linked.</p>

                <form class="mt-4 flex items-end gap-2 border-t border-hairline pt-4" @submit.prevent="addComponent">
                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Link a recipe</span>
                        <select
                            v-model="componentForm.child_recipe_id"
                            :disabled="!availableRecipes.length"
                            class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20 disabled:opacity-50"
                        >
                            <option value="">{{ availableRecipes.length ? 'Choose…' : 'No recipes available' }}</option>
                            <option v-for="r in availableRecipes" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </label>
                    <Button type="submit" :disabled="componentForm.processing || !componentForm.child_recipe_id">Add</Button>
                </form>
                <p v-if="componentForm.errors.child_recipe_id" class="mt-2 text-xs text-meat-strong">
                    {{ componentForm.errors.child_recipe_id }}
                </p>
            </Card>

            <!-- Expanded preview -->
            <Card>
                <div class="mb-4 font-serif text-lg font-semibold text-ink">Flattened to base ingredients</div>
                <div v-if="expanded.length" class="flex flex-wrap gap-2">
                    <Pill v-for="(item, idx) in expanded" :key="item.ingredient_id ?? idx" tone="neutral">
                        {{ item.name }}<span v-if="item.quantity_hint" class="text-subtle"> · {{ item.quantity_hint }}</span>
                    </Pill>
                </div>
                <p v-else class="text-sm text-muted">
                    Nothing to expand yet — add ingredients or sub-recipes to see the flattened list.
                </p>
            </Card>
        </div>
    </AppLayout>
</template>
