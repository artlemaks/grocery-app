<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Pill from '@/Components/Pill.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    list: { type: Object, default: null },
    groups: { type: Array, default: () => [] },
    allIngredients: { type: Array, default: () => [] },
});

const form = useForm({ ingredient_id: '', quantity: '' });

function complete() {
    router.post('/shopping/' + props.list.id + '/complete');
}

function toggle(item, event) {
    router.put(
        '/shopping/' + props.list.id + '/items/' + item.id,
        { is_checked: event.target.checked },
        { preserveScroll: true },
    );
}

function addItem() {
    form.post('/shopping/' + props.list.id + '/items', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Shopping List" />
    <AppLayout>
        <PageHeader title="Shopping List">
            <template #actions>
                <Button v-if="list" @click="complete">Complete shopping</Button>
            </template>
        </PageHeader>

        <!-- Empty state: no active list -->
        <Card v-if="!list">
            <div class="py-8 text-center">
                <div class="font-serif text-xl text-ink">No active list</div>
                <p class="mx-auto mt-1.5 max-w-sm text-sm text-muted">
                    Generate one from the Planner to start filling your basket.
                </p>
                <Link href="/planner" class="mt-4 inline-block">
                    <Button variant="ghost">Go to Planner</Button>
                </Link>
            </div>
        </Card>

        <template v-else>
            <div class="flex flex-col gap-4">
                <Card v-for="group in groups" :key="group.category">
                    <h2 class="mb-3 font-serif text-lg font-semibold text-ink">{{ group.category }}</h2>
                    <ul class="flex flex-col divide-y divide-hairline">
                        <li
                            v-for="item in group.items"
                            :key="item.id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <input
                                type="checkbox"
                                :checked="item.is_checked"
                                class="h-4 w-4 shrink-0 rounded border-hairline text-terracotta focus:ring-terracotta/30"
                                @change="toggle(item, $event)"
                            />
                            <span
                                :class="[
                                    'flex-1 text-sm',
                                    item.is_checked ? 'text-subtle line-through' : 'text-ink',
                                ]"
                            >
                                {{ item.name }}
                            </span>
                            <span class="text-sm text-muted">{{ item.quantity }}</span>
                            <Pill v-if="item.source === 'manual'" tone="neutral">Manual</Pill>
                        </li>
                    </ul>
                </Card>

                <Card v-if="!groups.length">
                    <div class="py-6 text-center text-sm text-muted">
                        This list is empty. Add an item below.
                    </div>
                </Card>

                <!-- Add item -->
                <Card>
                    <h2 class="mb-3 font-serif text-lg font-semibold text-ink">Add item</h2>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="addItem">
                        <label class="block flex-1 min-w-[200px]">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-label">Ingredient</span>
                            <select
                                v-model="form.ingredient_id"
                                class="w-full rounded-[10px] border border-hairline bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"
                            >
                                <option value="" disabled>Choose ingredient…</option>
                                <option v-for="ing in allIngredients" :key="ing.id" :value="ing.id">{{ ing.name }}</option>
                            </select>
                            <span v-if="form.errors.ingredient_id" class="mt-1 block text-xs text-meat-strong">{{ form.errors.ingredient_id }}</span>
                        </label>
                        <TextInput
                            v-model="form.quantity"
                            label="Quantity"
                            placeholder="e.g. 2"
                            :error="form.errors.quantity"
                        />
                        <Button type="submit" :disabled="form.processing">Add</Button>
                    </form>
                </Card>
            </div>
        </template>
    </AppLayout>
</template>
