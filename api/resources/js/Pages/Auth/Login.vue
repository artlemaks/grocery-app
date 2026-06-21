<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Sign in" />
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-terracotta shadow-[0_10px_24px_-10px_rgba(188,91,60,.7)]">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M12 12v4"/></svg>
                </div>
                <h1 class="font-serif text-4xl font-semibold tracking-tight">Larder</h1>
                <p class="mt-2 text-sm text-muted">Your private household kitchen.</p>
            </div>

            <form class="space-y-4 rounded-card border border-hairline bg-card p-6" @submit.prevent="submit">
                <TextInput v-model="form.email" label="Email" type="email" :error="form.errors.email" placeholder="artur@larder.test" />
                <TextInput v-model="form.password" label="Password" type="password" :error="form.errors.password" placeholder="••••••••" />
                <Button type="submit" variant="primary" class="w-full" :disabled="form.processing">Sign in</Button>
            </form>

            <p class="mt-4 text-center text-xs text-subtle">Seeded demo: artur@larder.test / password</p>
        </div>
    </div>
</template>
