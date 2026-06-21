<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';
import DietPill from '@/Components/DietPill.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    ingredients: { type: Array, default: () => [] },
    dietClasses: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    diet_class: 'plant',
    default_unit: '',
    category: '',
});

function submit() {
    form.post('/ingredients', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

// Resolve a substitute ingredient's name by id.
const byId = computed(() =>
    Object.fromEntries(props.ingredients.map((i) => [i.id, i])),
);

function substituteName(i) {
    if (!i.substitute_ingredient_id) return null;
    return byId.value[i.substitute_ingredient_id]?.name ?? null;
}

function setSubstitute(i, value) {
    router.put(
        '/ingredients/' + i.id + '/substitute',
        { substitute_ingredient_id: value || null },
        { preserveScroll: true },
    );
}

function remove(i) {
    router.delete('/ingredients/' + i.id, { preserveScroll: true });
}
</script>

<template>
    <Head title="Ingredients" />
    <AppLayout>
        <PageHeader
            title="Ingredient Library"
            subtitle="Your reusable kitchen ingredients."
        />

        <!-- Add ingredient -->
        <Card class="mb-6">
            <form
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                @submit.prevent="submit"
            >
                <div class="sm:col-span-2 lg:col-span-1">
                    <TextInput
                        v-model="form.name"
                        label="Name"
                        placeholder="e.g. Mince Beef"
                        :error="form.errors.name"
                    />
                </div>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Diet class</span>
                    <select
                        v-model="form.diet_class"
                        class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm capitalize text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                    >
                        <option v-for="d in dietClasses" :key="d" :value="d" class="capitalize">
                            {{ d }}
                        </option>
                    </select>
                    <span v-if="form.errors.diet_class" class="mt-1 block text-xs text-meat-strong">{{ form.errors.diet_class }}</span>
                </label>

                <TextInput
                    v-model="form.default_unit"
                    label="Unit"
                    placeholder="pack, jar…"
                    :error="form.errors.default_unit"
                />

                <TextInput
                    v-model="form.category"
                    label="Category"
                    placeholder="optional"
                    :error="form.errors.category"
                />

                <div class="flex items-end sm:col-span-2 lg:col-span-4">
                    <Button type="submit" :disabled="form.processing">Add</Button>
                </div>
            </form>
        </Card>

        <!-- List -->
        <Card v-if="ingredients.length" :padded="false">
            <ul class="divide-y divide-hairline">
                <li
                    v-for="i in ingredients"
                    :key="i.id"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-ink">{{ i.name }}</span>
                            <DietPill :dietClass="i.diet_class" />
                            <Pill v-if="i.requires_open_tracking" tone="frozen">Opens</Pill>
                        </div>
                        <div v-if="substituteName(i)" class="mt-1 text-xs text-muted">
                            Substitute: <span class="text-veg">{{ substituteName(i) }}</span>
                        </div>
                        <div v-if="i.default_unit || i.category" class="mt-1 text-xs text-subtle">
                            <span v-if="i.default_unit">{{ i.default_unit }}</span>
                            <span v-if="i.default_unit && i.category"> · </span>
                            <span v-if="i.category">{{ i.category }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="block">
                            <span class="sr-only">Substitute for {{ i.name }}</span>
                            <select
                                :value="i.substitute_ingredient_id ?? ''"
                                class="w-44 rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                                @change="setSubstitute(i, $event.target.value)"
                            >
                                <option value="">No substitute</option>
                                <option
                                    v-for="other in ingredients"
                                    v-show="other.id !== i.id"
                                    :key="other.id"
                                    :value="other.id"
                                    :disabled="other.id === i.id"
                                >
                                    {{ other.name }}
                                </option>
                            </select>
                        </label>
                        <Button variant="danger" @click="remove(i)">Remove</Button>
                    </div>
                </li>
            </ul>
        </Card>

        <!-- Empty state -->
        <Card v-else>
            <div class="py-8 text-center">
                <div class="font-serif text-xl text-ink">No ingredients yet</div>
                <p class="mt-1.5 text-sm text-muted">
                    Add your first ingredient above to start building your kitchen library.
                </p>
            </div>
        </Card>
    </AppLayout>
</template>
