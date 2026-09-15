<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage<any>();
const report = computed(() => page.props.report);
const rows = computed(() => page.props.rows ?? []);
const summary = computed(() => page.props.summary ?? []);
const filters = reactive({ ...page.props.filters });
const detailSections = computed(() => [
    { title: 'Ringkasan per Outlet', rows: page.props.payrollRows ?? [] },
    { title: 'Detail Gaji per Periode', rows: page.props.payrollByPeriodRows ?? [] },
    { title: 'Kasbon Berjalan', rows: page.props.outstandingKasbonRows ?? [] },
].filter((section) => section.rows.length));

const excluded = new Set(['id', 'outlet_id', 'menu_item_id', 'ingredient_id']);
const columns = computed(() => {
    const row = rows.value[0] ?? {};
    return Object.keys(row).filter((key) => !excluded.has(key) && typeof row[key] !== 'object');
});

function columnsFor(sectionRows: Record<string, unknown>[]) {
    return Object.keys(sectionRows[0] ?? {}).filter((key) => !excluded.has(key) && typeof sectionRows[0][key] !== 'object');
}

function label(key: string) {
    return key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function format(value: unknown, key: string) {
    if (value === null || value === undefined) return '-';
    if (typeof value === 'number' && /_pct$|percentage|percent/i.test(key)) {
        return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value)}%`;
    }
    if (typeof value === 'number' && /amount|revenue|omzet|salary|bonus|paid|deduction|refund|margin|hpp|value|total|profit|expense|aov|outstanding/i.test(key)) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
    }
    return typeof value === 'number' ? new Intl.NumberFormat('id-ID').format(value) : String(value);
}

function applyFilters() {
    router.get(`/app/reports/${report.value.slug}`, filters, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="report.title" />
    <AppLayout>
        <template #title>{{ report.title }}</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <nav aria-label="Daftar laporan" class="flex gap-2 overflow-x-auto pb-1">
                <Link v-for="item in page.props.reportNavigation ?? []" :key="item.slug" :href="`/app/reports/${item.slug}`" class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition" :class="item.slug === report.slug ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-stone-200 bg-white text-stone-600 hover:border-stone-300 hover:text-stone-950'">{{ item.title }}</Link>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Laporan</p>
                    <h2 class="mt-1 text-2xl font-semibold text-stone-950">{{ report.title }}</h2>
                </div>
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="applyFilters">
                    <label class="text-xs text-stone-500">Mulai<input v-model="filters.start_date" type="date" class="mt-1 block rounded-xl border-stone-200 text-sm"></label>
                    <label class="text-xs text-stone-500">Sampai<input v-model="filters.end_date" type="date" class="mt-1 block rounded-xl border-stone-200 text-sm"></label>
                    <select v-if="page.props.auth?.user?.role === 'admin'" v-model="filters.outlet_id" class="rounded-xl border-stone-200 text-sm">
                        <option :value="null">Semua outlet</option>
                        <option v-for="(name, id) in page.props.outlets" :key="id" :value="id">{{ name }}</option>
                    </select>
                    <button class="rounded-xl bg-stone-950 px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="item in summary" :key="item.label" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">{{ item.label }}</p>
                    <p class="mt-5 text-xl font-semibold text-stone-950">{{ item.value }}</p>
                </article>
            </div>

            <section v-if="rows.length || !detailSections.length" class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table v-if="rows.length" class="w-full min-w-[720px] text-left text-sm">
                        <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th v-for="column in columns" :key="column" class="px-5 py-4 font-semibold">{{ label(column) }}</th></tr></thead>
                        <tbody class="divide-y divide-stone-100"><tr v-for="(row, index) in rows" :key="index" class="hover:bg-stone-50"><td v-for="column in columns" :key="column" class="px-5 py-4 text-stone-700">{{ format(row[column], column) }}</td></tr></tbody>
                    </table>
                    <div v-else class="p-12 text-center"><p class="font-medium text-stone-700">Belum ada data</p><p class="mt-1 text-sm text-stone-500">Coba ubah rentang tanggal atau outlet.</p></div>
                </div>
            </section>

            <section v-for="section in detailSections" :key="section.title" class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="border-b border-stone-200 px-5 py-4"><h3 class="font-semibold text-stone-950">{{ section.title }}</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th v-for="column in columnsFor(section.rows)" :key="column" class="px-5 py-4 font-semibold">{{ label(column) }}</th></tr></thead>
                        <tbody class="divide-y divide-stone-100"><tr v-for="(row, index) in section.rows" :key="index" class="hover:bg-stone-50"><td v-for="column in columnsFor(section.rows)" :key="column" class="px-5 py-4 text-stone-700">{{ format(row[column], column) }}</td></tr></tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
