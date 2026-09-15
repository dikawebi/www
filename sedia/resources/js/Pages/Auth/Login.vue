<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/app/login');
</script>

<template>
    <Head title="Login" />
    <main class="grid min-h-screen place-items-center bg-stone-950 px-5 py-10">
        <section class="w-full max-w-md rounded-[2rem] bg-white p-7 shadow-2xl sm:p-10">
            <div class="mb-8">
                <div class="mb-5 grid size-12 place-items-center rounded-2xl bg-amber-400 text-lg font-black text-stone-950">S</div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Sedia workspace</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-950">Masuk ke aplikasi</h1>
                <p class="mt-2 text-sm text-stone-500">Kelola outlet, stok, dan penjualan dari satu dashboard.</p>
            </div>
            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="email">Email</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" class="w-full rounded-xl border-stone-300 px-4 py-3" required>
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="password">Password</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="current-password" class="w-full rounded-xl border-stone-300 px-4 py-3" required>
                </div>
                <label class="flex items-center gap-2 text-sm text-stone-600"><input v-model="form.remember" type="checkbox" class="rounded border-stone-300 text-amber-500"> Ingat saya</label>
                <button class="w-full rounded-xl bg-stone-950 px-4 py-3 font-semibold text-white transition hover:bg-stone-800 disabled:opacity-50" :disabled="form.processing">{{ form.processing ? 'Memproses...' : 'Masuk' }}</button>
            </form>
        </section>
    </main>
</template>
