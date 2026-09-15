<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/app/login', { onError: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk"/>
    <main class="min-h-screen bg-[#eaf2f5] p-3 sm:p-5 lg:grid lg:grid-cols-[1.08fr_.92fr]">
        <section class="relative hidden min-h-[calc(100vh-2.5rem)] overflow-hidden rounded-[2rem] bg-[#101a2f] p-10 text-white shadow-2xl shadow-slate-950/15 lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div class="relative z-10 flex items-center gap-3"><div class="grid size-11 place-items-center rounded-xl bg-cyan-400 text-lg font-black text-slate-950 shadow-[0_0_0_6px_rgba(34,211,238,.1)]">S</div><div><p class="font-semibold tracking-tight">Sedia</p><p class="text-[10px] uppercase tracking-[0.22em] text-slate-500">Operation suite</p></div></div>
            <div class="relative z-10 max-w-xl"><div class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-300"><span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></span>Workspace siap digunakan</div><h1 class="text-5xl font-semibold leading-[1.05] tracking-[-0.045em] xl:text-6xl">Operasional outlet,<br><span class="text-cyan-300">lebih terkendali.</span></h1><p class="mt-6 max-w-lg text-base leading-7 text-slate-400">Penjualan, persediaan, karyawan, dan laporan tersambung dalam satu ruang kerja yang cepat.</p>
                <div class="mt-10 grid grid-cols-3 gap-3"><article class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur"><span class="grid size-8 place-items-center rounded-lg bg-cyan-400/15 text-sm text-cyan-300">01</span><p class="mt-5 text-sm font-semibold">POS terintegrasi</p><p class="mt-1 text-xs leading-5 text-slate-500">Stok terpotong otomatis.</p></article><article class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur"><span class="grid size-8 place-items-center rounded-lg bg-violet-400/15 text-sm text-violet-300">02</span><p class="mt-5 text-sm font-semibold">Multi outlet</p><p class="mt-1 text-xs leading-5 text-slate-500">Satu kontrol terpusat.</p></article><article class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur"><span class="grid size-8 place-items-center rounded-lg bg-emerald-400/15 text-sm text-emerald-300">03</span><p class="mt-5 text-sm font-semibold">Data realtime</p><p class="mt-1 text-xs leading-5 text-slate-500">Keputusan lebih cepat.</p></article></div>
            </div>
            <div class="relative z-10 flex items-center justify-between text-[10px] uppercase tracking-[0.16em] text-slate-600"><span>Sedia workspace</span><span>Secure access</span></div>
            <div class="absolute -right-32 -top-32 size-[32rem] rounded-full border-[70px] border-cyan-400/[0.05]"></div><div class="absolute -bottom-40 -left-24 size-[28rem] rounded-full bg-violet-500/10 blur-3xl"></div><div class="absolute bottom-1/3 right-10 size-56 rounded-full bg-cyan-400/10 blur-3xl"></div>
        </section>

        <section class="flex min-h-[calc(100vh-1.5rem)] items-center justify-center px-3 py-10 sm:min-h-[calc(100vh-2.5rem)] sm:px-8">
            <div class="w-full max-w-[430px]">
                <div class="mb-10 flex items-center gap-3 lg:hidden"><div class="grid size-11 place-items-center rounded-xl bg-cyan-400 text-lg font-black text-slate-950">S</div><div><p class="font-semibold text-slate-950">Sedia</p><p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Operation suite</p></div></div>
                <div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-cyan-700">Selamat datang kembali</p><h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Masuk ke workspace</h2><p class="mt-3 text-sm leading-6 text-slate-500">Gunakan akun yang telah diberikan administrator.</p></div>

                <div v-if="Object.keys(form.errors).length" class="mt-6 flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-rose-100 text-xs font-bold">!</span><div><p class="text-xs font-bold uppercase tracking-wider text-rose-700">Tidak dapat masuk</p><p v-for="(message, key) in form.errors" :key="key" class="mt-1 text-sm">{{ message }}</p></div></div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <label class="block"><span class="text-sm font-medium text-slate-700">Alamat email</span><div class="relative mt-2"><span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400">@</span><input v-model="form.email" type="email" autocomplete="email" autofocus required class="w-full rounded-xl border border-slate-300 bg-white py-3.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10" placeholder="nama@perusahaan.com"></div></label>
                    <label class="block"><span class="text-sm font-medium text-slate-700">Password</span><div class="relative mt-2"><span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">••</span><input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 bg-white py-3.5 pl-10 pr-16 text-sm text-slate-900 shadow-sm transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10" placeholder="Masukkan password"><button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-cyan-700" @click="showPassword = !showPassword">{{ showPassword ? 'Sembunyi' : 'Lihat' }}</button></div></label>
                    <div class="flex items-center justify-between"><label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-cyan-500 focus:ring-cyan-500"> Ingat saya</label><span class="text-xs text-slate-400">Akses terenkripsi</span></div>
                    <button class="flex w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-3.5 font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-cyan-500 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing"><span v-if="form.processing" class="mr-2 size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>{{ form.processing ? 'Memverifikasi...' : 'Masuk ke Sedia' }}<span v-if="!form.processing" class="ml-2">→</span></button>
                </form>
                <p class="mt-8 text-center text-xs leading-5 text-slate-400">Jika tidak dapat masuk, hubungi administrator untuk memeriksa status akun dan hak akses.</p>
            </div>
        </section>
    </main>
</template>
