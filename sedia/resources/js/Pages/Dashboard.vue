<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

const page = usePage<any>();
const stats = computed(() => page.props.stats);
const rupiah = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const cards = computed(() => [
    { label: 'Pendapatan', value: rupiah(stats.value.revenue), note: 'Transaksi selesai', icon: 'Rp', tone: 'from-violet-500 to-indigo-600', wash: 'bg-violet-50 text-violet-700' },
    { label: 'Transaksi', value: stats.value.transactions, note: 'Seluruh riwayat', icon: '#', tone: 'from-cyan-500 to-sky-600', wash: 'bg-cyan-50 text-cyan-700' },
    { label: 'Bahan baku', value: stats.value.ingredients, note: 'Bahan aktif', icon: '◇', tone: 'from-emerald-500 to-teal-600', wash: 'bg-emerald-50 text-emerald-700' },
    { label: 'Menu aktif', value: stats.value.menus, note: 'Siap dijual', icon: '＋', tone: 'from-amber-400 to-orange-500', wash: 'bg-amber-50 text-amber-700' },
]);
const date = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
</script>

<template>
    <Head title="Dashboard"/>
    <AppLayout>
        <template #title>Ringkasan operasional</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                <div class="relative z-10 flex flex-col justify-between gap-7 lg:flex-row lg:items-end">
                    <div><div class="mb-4 inline-flex items-center gap-2 rounded-full bg-cyan-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-700"><span class="size-1.5 rounded-full bg-cyan-500"></span>{{ date }}</div><h2 class="max-w-2xl text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Semua operasi bisnis,<br><span class="text-slate-400">dalam satu pandangan.</span></h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Pantau penjualan, persediaan, dan aktivitas outlet tanpa berpindah ruang kerja.</p></div>
                    <div class="flex flex-wrap gap-2"><a href="/app/reports/sales-by-outlet" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">Lihat laporan</a><a href="/app/pos" class="rounded-xl bg-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-400">Buka POS <span class="ml-2">→</span></a></div>
                </div>
                <div class="absolute -right-10 -top-16 size-52 rounded-full bg-violet-200/35 blur-3xl"></div><div class="absolute right-24 top-12 size-36 rounded-full bg-cyan-200/40 blur-3xl"></div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in cards" :key="card.label" class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r" :class="card.tone"></div><div class="flex items-start justify-between"><div><p class="text-sm font-medium text-slate-500">{{ card.label }}</p><p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ card.value }}</p><p class="mt-1 text-xs text-slate-400">{{ card.note }}</p></div><span class="grid size-10 place-items-center rounded-xl text-xs font-bold" :class="card.wash">{{ card.icon }}</span></div>
                </article>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.5fr_1fr]">
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"><div class="mb-5 flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-violet-600">Quick access</p><h3 class="mt-1 font-semibold text-slate-950">Lanjutkan pekerjaan</h3></div><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-500">3 modul</span></div><div class="grid gap-3 sm:grid-cols-3"><a href="/app/master/menus" class="rounded-xl bg-amber-50 p-4 transition hover:bg-amber-100"><span class="grid size-9 place-items-center rounded-lg bg-amber-400 text-sm font-bold text-amber-950">01</span><h4 class="mt-5 text-sm font-semibold text-amber-950">Kelola menu</h4><p class="mt-1 text-xs leading-5 text-amber-800/70">Produk, resep, dan harga jual.</p></a><a href="/app/stock" class="rounded-xl bg-emerald-50 p-4 transition hover:bg-emerald-100"><span class="grid size-9 place-items-center rounded-lg bg-emerald-500 text-sm font-bold text-white">02</span><h4 class="mt-5 text-sm font-semibold text-emerald-950">Cek stok</h4><p class="mt-1 text-xs leading-5 text-emerald-800/70">Saldo bahan setiap outlet.</p></a><a href="/app/transactions" class="rounded-xl bg-cyan-50 p-4 transition hover:bg-cyan-100"><span class="grid size-9 place-items-center rounded-lg bg-cyan-500 text-sm font-bold text-slate-950">03</span><h4 class="mt-5 text-sm font-semibold text-cyan-950">Transaksi</h4><p class="mt-1 text-xs leading-5 text-cyan-800/70">Tinjau penjualan terbaru.</p></a></div></section>
                <section class="rounded-2xl bg-slate-950 p-5 text-white shadow-lg"><div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-400">Daily pulse</p><h3 class="mt-1 font-semibold">Kondisi sistem</h3></div><span class="size-2.5 rounded-full bg-emerald-400 shadow-[0_0_12px_#34d399]"></span></div><div class="mt-7 space-y-4"><div class="flex items-center justify-between border-b border-white/10 pb-3 text-sm"><span class="text-slate-400">Outlet aktif</span><strong>{{ page.props.auth?.currentOutlet?.name ?? 'Semua outlet' }}</strong></div><div class="flex items-center justify-between border-b border-white/10 pb-3 text-sm"><span class="text-slate-400">Status layanan</span><strong class="text-emerald-400">Normal</strong></div><a href="/app/operations/activity-logs" class="flex items-center justify-between rounded-xl bg-white/[0.06] px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white"><span>Lihat activity log</span><span>→</span></a></div></section>
            </div>
        </div>
    </AppLayout>
</template>
