<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";

const page = usePage<any>();
const mode = computed(() => page.props.mode);
const rows = computed(() => page.props.rows?.data ?? page.props.rows ?? []);
const title = computed(
    () =>
        (
            ({
                payrolls: "Penggajian",
                kasbons: "Kasbon",
                closing: "Tutup Kasir Harian",
                reorder: "Saran Reorder",
                "activity-logs": "Activity Log",
            }) as any
        )[mode.value],
);
const today = new Date().toISOString().slice(0, 10);
const payroll = reactive({
    outlet_id: "",
    employee_id: "",
    pay_date: today,
    period_start: today,
    period_end: today,
    base_salary: 0,
    bonus_masuk: 0,
    bonus_goreng: 0,
    kasbon_deduction: 0,
    status: "draft",
    note: "",
});
const kasbon = reactive({
    employee_id: "",
    amount: 0,
    trans_date: today,
    note: "",
});
const rupiah = (value: number) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value);
const payrollTotal = computed(
    () =>
        Number(payroll.base_salary) +
        Number(payroll.bonus_masuk) +
        Number(payroll.bonus_goreng) -
        Number(payroll.kasbon_deduction),
);
const closingSummary = computed(() => page.props.summary ?? {});
const queryDate = new URLSearchParams(page.url.split("?")[1] ?? "").get("date");
const filters = reactive({
    q: page.props.filters?.q ?? "",
    status: page.props.filters?.status ?? "",
    date_from: page.props.filters?.date_from ?? "",
    date_to: page.props.filters?.date_to ?? "",
    date: queryDate ?? today,
});
const processing = ref(false);
const permissionKey = computed(
    () =>
        (
            ({
                payrolls: "PayrollResource",
                kasbons: "KasbonResource",
                closing: "TutupKasirHarian",
                reorder: "SaranReorder",
                "activity-logs": "ActivityLogResource",
            }) as Record<string, string>
        )[mode.value],
);
const can = (actionName: "create" | "edit") =>
    Boolean(
        page.props.auth?.permissions?.[permissionKey.value]?.[
            `can_${actionName}`
        ],
    );
function post(url: string, data: Record<string, any>) {
    if (processing.value) return;
    processing.value = true;
    router.post(url, data, {
        onFinish: () => {
            processing.value = false;
        },
    });
}
function submitPayroll() {
    post("/app/operations/payrolls", payroll);
}
function submitKasbon() {
    post("/app/operations/kasbons", kasbon);
}
function kasbonAction(id: number, actionName: string) {
    post(`/app/operations/kasbons/${id}/${actionName}`, {});
}
function payrollAction(id: number, actionName: string) {
    post(`/app/operations/payrolls/${id}/${actionName}`, {});
}
function applyFilters() {
    router.get(
        `/app/operations/${mode.value}`,
        mode.value === "closing" ? { date: filters.date } : filters,
        { preserveState: true, replace: true },
    );
}
function resetFilters() {
    Object.assign(filters, {
        q: "",
        status: "",
        date_from: "",
        date_to: "",
        date: today,
    });
    applyFilters();
}
function confirmAction(message: string) {
    return window.confirm(message);
}
const confirm = confirmAction;
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <template #title>{{ title }}</template>
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase tracking-[0.22em] text-lime-700"
                    >
                        Operations
                    </p>
                    <h2 class="mt-1 text-2xl font-semibold">{{ title }}</h2>
                </div>
            </div>

            <form
                v-if="['payrolls', 'kasbons', 'activity-logs'].includes(mode)"
                class="grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-5"
                @submit.prevent="applyFilters"
            >
                <input
                    v-model="filters.q"
                    aria-label="Cari data operasional"
                    class="rounded-xl border-slate-200 text-sm"
                    placeholder="Cari nama/aktivitas"
                /><select
                    v-model="filters.status"
                    aria-label="Status"
                    class="rounded-xl border-slate-200 text-sm"
                >
                    <option value="">Semua status</option>
                    <option
                        v-for="status in [
                            'draft',
                            'paid',
                            'cancelled',
                            'pending',
                            'approved',
                            'rejected',
                        ]"
                        :key="status"
                        :value="status"
                    >
                        {{ status }}
                    </option></select
                ><input
                    v-model="filters.date_from"
                    aria-label="Tanggal mulai"
                    type="date"
                    class="rounded-xl border-slate-200 text-sm"
                /><input
                    v-model="filters.date_to"
                    aria-label="Tanggal akhir"
                    type="date"
                    class="rounded-xl border-slate-200 text-sm"
                />
                <div class="flex gap-2">
                    <button
                        class="flex-1 rounded-xl bg-slate-900 px-3 text-sm text-white"
                    >
                        Filter</button
                    ><button
                        type="button"
                        class="rounded-xl border px-3 text-sm"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </div>
            </form>
            <form
                v-if="mode === 'closing'"
                class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3"
                @submit.prevent="applyFilters"
            >
                <label class="text-xs text-slate-500"
                    >Tanggal closing<input
                        v-model="filters.date"
                        type="date"
                        class="mt-1 block rounded-xl border-slate-200 text-sm" /></label
                ><button
                    class="self-end rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white"
                >
                    Tampilkan
                </button>
            </form>

            <section
                v-if="mode === 'payrolls' && can('create')"
                class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold">Tambah penggajian</h3>
                        <p class="mt-1 text-sm text-stone-500">
                            Total dihitung ulang oleh server.
                        </p>
                    </div>
                    <span
                        class="rounded-xl bg-lime-100 px-3 py-2 text-sm font-semibold text-lime-800"
                        >{{ rupiah(payrollTotal) }}</span
                    >
                </div>
                <form
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="submitPayroll"
                >
                    <label class="field"
                        >Outlet<select v-model="payroll.outlet_id">
                            <option value="">Pilih outlet</option>
                            <option
                                v-for="(name, id) in page.props.outlets"
                                :key="id"
                                :value="id"
                            >
                                {{ name }}
                            </option>
                        </select></label
                    >
                    <label class="field"
                        >Karyawan<select v-model="payroll.employee_id">
                            <option value="">Pilih karyawan</option>
                            <option
                                v-for="employee in page.props.employees"
                                :key="employee.id"
                                :value="employee.id"
                            >
                                {{ employee.name }}
                            </option>
                        </select></label
                    >
                    <label class="field"
                        >Tanggal bayar<input
                            v-model="payroll.pay_date"
                            type="date" /></label
                    ><label class="field"
                        >Mulai periode<input
                            v-model="payroll.period_start"
                            type="date" /></label
                    ><label class="field"
                        >Akhir periode<input
                            v-model="payroll.period_end"
                            type="date" /></label
                    ><label class="field"
                        >Gaji pokok<input
                            v-model.number="payroll.base_salary"
                            type="number"
                            min="0" /></label
                    ><label class="field"
                        >Bonus masuk<input
                            v-model.number="payroll.bonus_masuk"
                            type="number"
                            min="0" /></label
                    ><label class="field"
                        >Bonus goreng<input
                            v-model.number="payroll.bonus_goreng"
                            type="number"
                            min="0" /></label
                    ><label class="field"
                        >Potongan kasbon<input
                            v-model.number="payroll.kasbon_deduction"
                            type="number"
                            min="0" /></label
                    ><button
                        :disabled="processing"
                        class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50 sm:col-span-2 lg:col-span-4"
                    >
                        {{ processing ? "Menyimpan..." : "Simpan penggajian" }}
                    </button>
                </form>
            </section>

            <section
                v-if="mode === 'kasbons' && can('create')"
                class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-5">
                    <h3 class="font-semibold">Ajukan kasbon</h3>
                    <p class="mt-1 text-sm text-stone-500">
                        Pengajuan akan berstatus pending sampai disetujui admin.
                    </p>
                </div>
                <form
                    class="grid gap-4 sm:grid-cols-3"
                    @submit.prevent="submitKasbon"
                >
                    <label class="field"
                        >Karyawan<select v-model="kasbon.employee_id">
                            <option value="">Pilih karyawan</option>
                            <option
                                v-for="employee in page.props.employees"
                                :key="employee.id"
                                :value="employee.id"
                            >
                                {{ employee.name }}
                            </option>
                        </select></label
                    ><label class="field"
                        >Nominal<input
                            v-model.number="kasbon.amount"
                            type="number"
                            min="1" /></label
                    ><label class="field"
                        >Tanggal<input
                            v-model="kasbon.trans_date"
                            type="date" /></label
                    ><button
                        :disabled="processing"
                        class="rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50 sm:col-span-3"
                    >
                        {{ processing ? "Mengajukan..." : "Ajukan kasbon" }}
                    </button>
                </form>
            </section>

            <section
                v-if="mode === 'closing'"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <article class="metric">
                    <span>Total transaksi</span
                    ><strong>{{
                        closingSummary.transaction_count ?? 0
                    }}</strong>
                </article>
                <article class="metric">
                    <span>Total omzet</span
                    ><strong>{{
                        rupiah(Number(closingSummary.total ?? 0))
                    }}</strong>
                </article>
                <article
                    v-for="(amount, method) in closingSummary.payments ?? {}"
                    :key="method"
                    class="metric"
                >
                    <span class="capitalize">{{ method }}</span
                    ><strong>{{ rupiah(Number(amount)) }}</strong>
                </article>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead
                            class="border-b bg-stone-50 text-xs uppercase text-stone-500"
                        >
                            <tr>
                                <th class="px-5 py-4">Nama / Item</th>
                                <th class="px-5 py-4">Outlet</th>
                                <th class="px-5 py-4">Status / Detail</th>
                                <th class="px-5 py-4">Nominal</th>
                                <th class="px-5 py-4">Diproses oleh</th>
                                <th
                                    v-if="
                                        ['kasbons', 'payrolls'].includes(mode) &&
                                        can('edit') &&
                                        page.props.auth?.user?.role === 'admin'
                                    "
                                    class="px-5 py-4"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr
                                v-for="row in rows"
                                :key="
                                    row.id ??
                                    row.cashier_name ??
                                    row.ingredient_name
                                "
                            >
                                <td class="px-5 py-4 font-medium">
                                    {{
                                        row.employee?.name ??
                                        row.user?.name ??
                                        row.cashier_name ??
                                        row.ingredient_name ??
                                        row.action ??
                                        "-"
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    {{
                                        row.outlet?.name ??
                                        row.outlet_name ??
                                        "-"
                                    }}
                                </td>
                                <td class="px-5 py-4 text-stone-500">
                                    {{
                                        row.status ??
                                        (row.suggested
                                            ? `${row.current} / min ${row.min_stock}`
                                            : (row.description ??
                                              `${row.transaction_count ?? 0} transaksi`))
                                    }}
                                </td>
                                <td class="px-5 py-4">
                                    {{
                                        row.amount
                                            ? rupiah(Number(row.amount))
                                            : row.total_salary
                                              ? rupiah(Number(row.total_salary))
                                              : row.total
                                              ? rupiah(Number(row.total))
                                              : row.suggested
                                                ? `${row.suggested} ${row.unit}`
                                                : "-"
                                    }}
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500">
                                    {{ row.reviewer?.name ?? row.processor?.name ?? "-"
                                    }}<span
                                        v-if="row.reviewed_at || row.processed_at"
                                        class="block text-[10px]"
                                        >{{ row.reviewed_at ?? row.processed_at }}</span
                                    >
                                </td>
                                <td
                                    v-if="
                                        ['kasbons', 'payrolls'].includes(mode) &&
                                        can('edit') &&
                                        page.props.auth?.user?.role === 'admin'
                                    "
                                    class="space-x-3 whitespace-nowrap px-5 py-4"
                                >
                                    <template v-if="mode === 'kasbons'"><button
                                        v-if="row.status === 'pending'"
                                        :disabled="processing"
                                        class="text-emerald-700 disabled:opacity-40"
                                        @click="
                                            confirm('Setujui kasbon ini?') &&
                                            kasbonAction(row.id, 'approve')
                                        "
                                    >
                                        Setujui</button
                                    ><button
                                        v-if="row.status === 'pending'"
                                        :disabled="processing"
                                        class="text-red-600 disabled:opacity-40"
                                        @click="
                                            confirm('Tolak kasbon ini?') &&
                                            kasbonAction(row.id, 'reject')
                                        "
                                    >
                                        Tolak
                                    </button></template><template v-else><button
                                        v-if="row.status === 'draft'"
                                        :disabled="processing"
                                        class="text-emerald-700 disabled:opacity-40"
                                        @click="confirm('Tandai penggajian sudah dibayar?') && payrollAction(row.id, 'pay')"
                                    >Bayar</button><button
                                        v-if="row.status === 'draft'"
                                        :disabled="processing"
                                        class="text-red-600 disabled:opacity-40"
                                        @click="confirm('Batalkan penggajian ini?') && payrollAction(row.id, 'cancel')"
                                    >Batalkan</button></template>
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td
                                    :colspan="
                                        ['kasbons', 'payrolls'].includes(mode) &&
                                        can('edit') &&
                                        page.props.auth?.user?.role === 'admin'
                                            ? 6
                                            : 5
                                    "
                                    class="p-12 text-center text-stone-500"
                                >
                                    Belum ada data.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="page.props.rows?.links ?? []" />
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.field {
    display: block;
    color: #57534e;
    font-size: 0.8rem;
}
.field input,
.field select {
    display: block;
    width: 100%;
    margin-top: 0.35rem;
    border: 1px solid #e7e5e4;
    border-radius: 0.8rem;
    background: #fafaf9;
    padding: 0.7rem 0.8rem;
    font-size: 0.875rem;
    color: #1c1917;
}
.metric {
    border: 1px solid #e7e5e4;
    border-radius: 1.5rem;
    background: #fff;
    padding: 1.25rem;
    box-shadow: 0 1px 2px #00000008;
}
.metric span {
    display: block;
    color: #78716c;
    font-size: 0.75rem;
}
.metric strong {
    display: block;
    margin-top: 0.8rem;
    color: #1c1917;
    font-size: 1.25rem;
}
</style>
