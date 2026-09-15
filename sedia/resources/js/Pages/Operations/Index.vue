<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const page = usePage<any>();
const mode = computed(() => page.props.mode);
const rows = computed(() => page.props.rows?.data ?? page.props.rows ?? []);
const title = computed(() => ({ payrolls: 'Penggajian', kasbons: 'Kasbon', closing: 'Tutup Kasir Harian', reorder: 'Saran Reorder', 'activity-logs': 'Activity Log' } as any)[mode.value]);
const today = new Date().toISOString().slice(0, 10);
const payroll = reactive({ outlet_id: '', employee_id: '', pay_date: today, period_start: today, period_end: today, base_salary: 0, bonus_masuk: 0, bonus_goreng: 0, kasbon_deduction: 0, status: 'draft', note: '' });
const kasbon = reactive({ employee_id: '', amount: 0, trans_date: today, note: '' });
const rupiah = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const payrollTotal = computed(() => Number(payroll.base_salary) + Number(payroll.bonus_masuk) + Number(payroll.bonus_goreng) - Number(payroll.kasbon_deduction));
const closingSummary = computed(() => page.props.summary ?? {});
function submitPayroll() { router.post('/app/operations/payrolls', payroll); }
function submitKasbon() { router.post('/app/operations/kasbons', kasbon); }
function action(id: number, actionName: string) { router.post(`/app/operations/kasbons/${id}/${actionName}`); }
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <template #title>{{ title }}</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-lime-700">Operations</p><h2 class="mt-1 text-2xl font-semibold">{{ title }}</h2></div>
            </div>

            <section v-if="mode === 'payrolls'" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start justify-between"><div><h3 class="font-semibold">Tambah penggajian</h3><p class="mt-1 text-sm text-stone-500">Total dihitung ulang oleh server.</p></div><span class="rounded-xl bg-lime-100 px-3 py-2 text-sm font-semibold text-lime-800">{{ rupiah(payrollTotal) }}</span></div>
                <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submitPayroll">
                    <label class="field">Outlet<select v-model="payroll.outlet_id"><option value="">Pilih outlet</option><option v-for="(name, id) in page.props.outlets" :key="id" :value="id">{{ name }}</option></select></label>
                    <label class="field">Karyawan<select v-model="payroll.employee_id"><option value="">Pilih karyawan</option><option v-for="employee in page.props.employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option></select></label>
                    <label class="field">Tanggal bayar<input v-model="payroll.pay_date" type="date"></label><label class="field">Mulai periode<input v-model="payroll.period_start" type="date"></label><label class="field">Akhir periode<input v-model="payroll.period_end" type="date"></label><label class="field">Gaji pokok<input v-model.number="payroll.base_salary" type="number" min="0"></label><label class="field">Bonus masuk<input v-model.number="payroll.bonus_masuk" type="number" min="0"></label><label class="field">Bonus goreng<input v-model.number="payroll.bonus_goreng" type="number" min="0"></label><label class="field">Potongan kasbon<input v-model.number="payroll.kasbon_deduction" type="number" min="0"></label><button class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white sm:col-span-2 lg:col-span-4">Simpan penggajian</button>
                </form>
            </section>

            <section v-if="mode === 'kasbons'" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"><div class="mb-5"><h3 class="font-semibold">Ajukan kasbon</h3><p class="mt-1 text-sm text-stone-500">Pengajuan akan berstatus pending sampai disetujui admin.</p></div><form class="grid gap-4 sm:grid-cols-3" @submit.prevent="submitKasbon"><label class="field">Karyawan<select v-model="kasbon.employee_id"><option value="">Pilih karyawan</option><option v-for="employee in page.props.employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option></select></label><label class="field">Nominal<input v-model.number="kasbon.amount" type="number" min="1"></label><label class="field">Tanggal<input v-model="kasbon.trans_date" type="date"></label><button class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white sm:col-span-3">Ajukan kasbon</button></form></section>

            <section v-if="mode === 'closing'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><article class="metric"><span>Total transaksi</span><strong>{{ closingSummary.transaction_count ?? 0 }}</strong></article><article class="metric"><span>Total omzet</span><strong>{{ rupiah(Number(closingSummary.total ?? 0)) }}</strong></article><article v-for="(amount, method) in (closingSummary.payments ?? {})" :key="method" class="metric"><span class="capitalize">{{ method }}</span><strong>{{ rupiah(Number(amount)) }}</strong></article></section>

            <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b bg-stone-50 text-xs uppercase text-stone-500"><tr><th class="px-5 py-4">Nama / Item</th><th class="px-5 py-4">Outlet</th><th class="px-5 py-4">Status / Detail</th><th class="px-5 py-4">Nominal</th><th class="px-5 py-4">Aksi</th></tr></thead><tbody class="divide-y divide-stone-100"><tr v-for="row in rows" :key="row.id ?? row.ingredient_name"><td class="px-5 py-4 font-medium">{{ row.employee?.name ?? row.user?.name ?? row.cashier_name ?? row.ingredient_name ?? row.action ?? '-' }}</td><td class="px-5 py-4">{{ row.outlet?.name ?? row.outlet_name ?? '-' }}</td><td class="px-5 py-4 text-stone-500">{{ row.status ?? (row.suggested ? `${row.current} / min ${row.min_stock}` : row.description ?? `${row.transaction_count ?? 0} transaksi`) }}</td><td class="px-5 py-4">{{ row.amount ? rupiah(Number(row.amount)) : row.total ? rupiah(Number(row.total)) : row.suggested ? `${row.suggested} ${row.unit}` : '-' }}</td><td class="space-x-3 px-5 py-4"><button v-if="mode === 'kasbons' && row.status === 'pending'" class="text-emerald-700" @click="action(row.id, 'approve')">Setujui</button><button v-if="mode === 'kasbons' && row.status === 'pending'" class="text-red-600" @click="action(row.id, 'reject')">Tolak</button></td></tr><tr v-if="!rows.length"><td colspan="5" class="p-12 text-center text-stone-500">Belum ada data.</td></tr></tbody></table></div><Pagination :links="page.props.rows?.links ?? []" /></section>
        </div>
    </AppLayout>
</template>

<style scoped>
.field { display: block; color: #57534e; font-size: .8rem; }
.field input, .field select { display: block; width: 100%; margin-top: .35rem; border: 1px solid #e7e5e4; border-radius: .8rem; background: #fafaf9; padding: .7rem .8rem; font-size: .875rem; color: #1c1917; }
.metric { border: 1px solid #e7e5e4; border-radius: 1.5rem; background: #fff; padding: 1.25rem; box-shadow: 0 1px 2px #00000008; }
.metric span { display: block; color: #78716c; font-size: .75rem; }
.metric strong { display: block; margin-top: .8rem; color: #1c1917; font-size: 1.25rem; }
</style>
