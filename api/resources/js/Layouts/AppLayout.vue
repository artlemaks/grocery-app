<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const nav = [
    { label: 'Home', href: '/dashboard', icon: 'M3 11l9-8 9 8M5 10v10h14V10' },
    { label: 'Recipes', href: '/recipes', icon: 'M6 8h12l-1 12H7zM9 8V6a3 3 0 0 1 6 0v2' },
    { label: 'Ingredients', href: '/ingredients', icon: 'M4 7h16M4 12h16M4 17h10' },
    { label: 'Planner', href: '/planner', icon: 'M4 5h16v16H4zM4 9h16M9 5v16' },
    { label: 'Shopping', href: '/shopping', icon: 'M6 6h15l-1.5 9h-12zM6 6L5 3H2M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z' },
    { label: 'Inventory', href: '/inventory', icon: 'M3 7l9-4 9 4-9 4zM3 7v10l9 4 9-4V7' },
    { label: 'Cook', href: '/cook', icon: 'M5 11a7 7 0 0114 0H5zM3 11h18M7 20h10' },
    { label: 'Reconcile', href: '/reconcile', icon: 'M21 12a9 9 0 11-3-6.7L21 8M21 3v5h-5' },
];

const isActive = (href) => page.url.startsWith(href);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="hidden w-64 shrink-0 flex-col border-r border-hairline bg-paper px-5 py-7 md:flex">
            <div class="mb-9 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-terracotta shadow-[0_10px_24px_-10px_rgba(188,91,60,.7)]">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M12 12v4"/></svg>
                </div>
                <span class="font-serif text-2xl font-semibold tracking-tight">Larder</span>
            </div>

            <nav class="flex flex-1 flex-col gap-1">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-[10px] px-3 py-2 text-sm font-medium transition',
                        isActive(item.href) ? 'bg-terracotta/10 text-terracotta' : 'text-muted hover:bg-card hover:text-ink',
                    ]"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path :d="item.icon"/></svg>
                    {{ item.label }}
                </Link>
            </nav>

            <div class="mt-4 border-t border-hairline pt-4">
                <div class="mb-2 px-1 text-sm">
                    <div class="font-semibold text-ink">{{ user?.name }}</div>
                    <div class="text-xs capitalize text-subtle">{{ user?.diet_type }}</div>
                </div>
                <button class="w-full rounded-[10px] px-3 py-2 text-left text-sm text-muted hover:bg-card hover:text-ink" @click="logout">
                    Sign out
                </button>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 px-6 py-8 md:px-12 md:py-10">
            <div class="mx-auto max-w-5xl">
                <slot />
            </div>
        </main>
    </div>
</template>
