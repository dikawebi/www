<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const page = usePage<any>();
const rows = computed(() => page.props.rows?.data ?? []);
const filters = reactive({ q: page.props.filters?.q ?? '', status: page.props.filters?.status ?? '', date_from: page.props.filters?.date_from ?? '', date_to: page.props.filters?.date_to ?? '' });
const processing = ref(false);
const canVoid = computed(() => page.props.auth?.user?.role === 'admin' && Boolean(page.props.auth?.permissions?.SalesTransactionResource?.can_edit));
const rupiah = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);

function voidTransaction(id: number) {
    if (processing.value || !confirm('Void transaksi ini?')) return;
    processing.value = true;
    router.post(`/app/transactions/${id}/void`, {}, { onFinish: () => { processing.value = false; } });
}
function applyFilters() { router.get('/app/transactions', filters, { preserveState: true, replace: true }); }
function resetFilters() { Object.assign(filters, { q: '', status: '', date_from: '', date_to: '' }); applyFilters(); }
</script>

<template>
    <Head title="Transaksi"/>
    <AppLayout>
        <template #title>Transaksi</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Penjualan</p><h2 class="mt-1 text-2xl font-semibold">Riwayat transaksi</h2></div>
            <form class="grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="applyFilters">
                <input v-model="filters.q" aria-label="Cari transaksi" placeholder="Cari invoice, kasir, outlet" class="rounded-xl border-stone-200 text-sm">
                <select v-model="filters.status" aria-label="Status transaksi" class="rounded-xl border-stone-200 text-sm"><option value="">Semua status</option><option value="completed">Completed</option><option value="voided">Voided</option></select>
                <input v-model="filters.date_from" aria-label="Tanggal mulai" type="date" class="rounded-xl border-stone-200 text-sm">
                <input v-model="filters.date_to" aria-label="Tanggal akhir" type="date" class="rounded-xl border-stone-200 text-sm">
                <div class="flex gap-2"><button class="flex-1 rounded-xl bg-stone-900 px-3 py-2 text-sm font-semibold text-white">Filter</button><button type="button" class="rounded-xl border border-stone-200 px-3 py-2 text-sm" @click="resetFilters">Reset</button></div>
            </form>
            <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm"><thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-4">Invoice</th><th class="px-5 py-4">Tanggal</th><th class="px-5 py-4">Outlet</th><th class="px-5 py-4">Kasir</th><th class="px-5 py-4">Total</th><th class="px-5 py-4">Status</th><th v-if="canVoid" class="px-5 py-4">Aksi</th></tr></thead><tbody class="divide-y divide-stone-100"><tr v-for="row in rows" :key="row.id"><td class="px-5 py-4 font-medium">{{ row.invoice_number }}</td><td class="px-5 py-4 text-stone-500">{{ row.transaction_date }}</td><td class="px-5 py-4">{{ row.outlet_name }}</td><td class="px-5 py-4">{{ row.cashier_name }}</td><td class="px-5 py-4 font-semibold">{{ rupiah(row.total_amount) }}</td><td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs" :class="row.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">{{ row.status }}</span></td><td v-if="canVoid" class="px-5 py-4"><button v-if="row.status === 'completed'" :disabled="processing" class="text-red-600 disabled:opacity-40" @click="voidTransaction(row.id)">Void</button></td></tr><tr v-if="!rows.length"><td :colspan="canVoid ? 7 : 6" class="p-12 text-center text-stone-500">Belum ada transaksi.</td></tr></tbody></table></div>
                <Pagination :links="page.props.rows?.links ?? []" />
            </section>
        </div>
    </AppLayout>
</template>
