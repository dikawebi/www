<script setup lang="ts">
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import ToastStack from "../Components/ToastStack.vue";

const page = usePage<any>();
const open = ref(false);
const user = computed(() => page.props.auth?.user);
const currentOutlet = computed(() => page.props.auth?.currentOutlet);
const outlets = computed(() => page.props.auth?.outlets ?? {});
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const loading = ref(false);
const notificationOpen = ref(false);
const notifications = computed(
    () => page.props.notifications ?? { unread_count: 0, items: [] },
);

const navigation: {
    label: string;
    adminOnly?: boolean;
    items: { label: string; href: string; icon: string; permission?: string; adminOnly?: boolean }[];
}[] = [
    {
        label: "Ringkasan",
        items: [{ label: "Dashboard", href: "/app", icon: "✦" }],
    },
    {
        label: "Penjualan",
        items: [
            {
                label: "POS Kasir",
                href: "/app/pos",
                icon: "＋",
                permission: "Pos",
            },
            {
                label: "Transaksi",
                href: "/app/transactions",
                icon: "▤",
                permission: "SalesTransactionResource",
            },
        ],
    },
    {
        label: "Master Data",
        items: [
            {
                label: "Outlet",
                href: "/app/master/outlets",
                icon: "⌂",
                permission: "OutletResource",
            },
            {
                label: "Menu",
                href: "/app/master/menus",
                icon: "◈",
                permission: "MenuItemResource",
            },
            {
                label: "Bahan Baku",
                href: "/app/master/ingredients",
                icon: "◇",
                permission: "IngredientResource",
            },
            {
                label: "Karyawan",
                href: "/app/master/employees",
                icon: "♙",
                permission: "EmployeeResource",
            },
            {
                label: "Pengeluaran",
                href: "/app/master/expenses",
                icon: "↘",
                permission: "ExpenseResource",
            },
            {
                label: "Pengguna",
                href: "/app/master/users",
                icon: "♟",
                permission: "UserResource",
                adminOnly: true,
            },
        ],
    },
    {
        label: "Persediaan",
        items: [
            {
                label: "Saldo Stok",
                href: "/app/stock",
                icon: "▦",
                permission: "StockResource",
            },
            {
                label: "Transfer Stok",
                href: "/app/stock-transfers",
                icon: "⇄",
                permission: "StockTransferResource",
            },
            {
                label: "Stock Opname",
                href: "/app/stock-opnames",
                icon: "✓",
                permission: "StockOpnameResource",
            },
            {
                label: "Saran Reorder",
                href: "/app/operations/reorder",
                icon: "↻",
                permission: "SaranReorder",
            },
        ],
    },
    {
        label: "Operasional",
        items: [
            {
                label: "Penggajian",
                href: "/app/operations/payrolls",
                icon: "◫",
                permission: "PayrollResource",
            },
            {
                label: "Kasbon",
                href: "/app/operations/kasbons",
                icon: "◎",
                permission: "KasbonResource",
            },
            {
                label: "Tutup Kasir",
                href: "/app/operations/closing",
                icon: "◷",
                permission: "TutupKasirHarian",
            },
        ],
    },
    {
        label: "Insight",
        items: [
            {
                label: "Laporan",
                href: "/app/reports/sales-by-outlet",
                icon: "⌁",
                permission: "SalesByOutletReport",
            },
            {
                label: "Activity Log",
                href: "/app/operations/activity-logs",
                icon: "☷",
                permission: "ActivityLogResource",
            },
        ],
    },
    {
        label: "Konfigurasi",
        adminOnly: true,
        items: [
            { label: "Branding", href: "/app/settings/branding", icon: "◉" },
            {
                label: "Permission",
                href: "/app/settings/permissions",
                icon: "⚙",
            },
        ],
    },
];
const navGroups = computed(() =>
    navigation
        .filter((group) => !group.adminOnly || user.value?.role === "admin")
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) =>
                    (!item.adminOnly || user.value?.role === "admin") &&
                    (!item.permission ||
                        user.value?.role === "admin" ||
                        page.props.auth?.permissions?.[item.permission]?.can_view),
            ),
        }))
        .filter((group) => group.items.length),
);

function active(href: string) {
    return href === "/app"
        ? page.url === "/app"
        : page.url.startsWith(href.split("/").slice(0, 4).join("/"));
}
function selectOutlet(event: Event) {
    router.post("/dashboard/outlet-context", {
        selected_outlet_id: (event.target as HTMLSelectElement).value || null,
    });
}
function logout() {
    router.post("/app/logout");
}
function readNotification(notification: any) {
    router.post(
        `/app/notifications/${notification.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (notification.data?.url) router.visit(notification.data.url);
            },
        },
    );
}
function readAllNotifications() {
    router.post("/app/notifications/read-all", {}, { preserveScroll: true });
}
router.on("start", () => {
    loading.value = true;
});
router.on("finish", () => {
    loading.value = false;
});
</script>

<template>
    <div
        class="spectrum-shell min-h-screen bg-[#f6f7f4] text-stone-900 lg:flex lg:gap-3 lg:p-3"
        :data-design="page.props.design || 'lime'"
    >
        <div
            v-if="loading"
            class="fixed inset-x-0 top-0 z-[100] h-1 bg-[#d8f36a] shadow-[0_0_18px_#d8f36a]"
        />
        <div
            v-if="open"
            class="fixed inset-0 z-30 bg-stone-950/40 backdrop-blur-sm lg:hidden"
            @click="open = false"
        />
        <aside
            class="spectrum-sidebar fixed inset-y-0 left-0 z-40 flex w-[280px] -translate-x-full flex-col bg-[#101a2f] px-4 py-5 text-stone-200 shadow-2xl transition-transform lg:static lg:inset-auto lg:translate-x-0 lg:rounded-[1.4rem] lg:shadow-[0_20px_50px_rgba(15,23,42,.18)]"
            :class="{ 'translate-x-0': open }"
        >
            <div class="mb-7 flex items-center gap-3 px-2">
                <div
                    class="grid size-10 place-items-center rounded-xl bg-[#22d3ee] text-base font-black text-slate-950 shadow-[0_0_0_5px_rgba(34,211,238,0.1)]"
                >
                    S
                </div>
                <div>
                    <p class="font-semibold tracking-tight text-white">Sedia</p>
                    <p
                        class="text-[10px] uppercase tracking-[0.2em] text-slate-500"
                    >
                        Operation suite
                    </p>
                </div>
            </div>
            <nav class="space-y-5 overflow-y-auto pr-1">
                <section v-for="group in navGroups" :key="group.label">
                    <p
                        class="mb-1.5 px-3 text-[9px] font-bold uppercase tracking-[0.24em] text-slate-600"
                    >
                        {{ group.label }}
                    </p>
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] transition"
                            :class="
                                active(item.href)
                                    ? 'bg-[#22d3ee] font-semibold text-slate-950 shadow-lg shadow-cyan-950/20'
                                    : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'
                            "
                            @click="open = false"
                            ><span
                                class="grid size-7 place-items-center rounded-lg text-xs"
                                :class="
                                    active(item.href)
                                        ? 'bg-slate-950/10'
                                        : 'bg-white/[0.05] text-slate-500 group-hover:text-slate-200'
                                "
                                >{{ item.icon }}</span
                            >{{ item.label }}</Link
                        >
                    </div>
                </section>
            </nav>
            <div
                class="mt-auto rounded-xl border border-white/[0.08] bg-white/[0.05] p-3"
            >
                <p
                    class="text-[9px] font-bold uppercase tracking-[0.2em] text-slate-600"
                >
                    Signed in as
                </p>
                <p class="mt-1.5 truncate text-sm font-medium text-white">
                    {{ user?.name }}
                </p>
                <div class="mt-0.5 flex items-center justify-between">
                    <p class="text-xs capitalize text-slate-500">{{ user?.role }}</p>
                    <button class="text-xs text-slate-400 hover:text-rose-300" @click="logout">Keluar</button>
                </div>
            </div>
        </aside>
        <main
            class="spectrum-workspace min-w-0 flex-1 bg-white/55 lg:overflow-hidden lg:rounded-[1.4rem] lg:border lg:border-white/80 lg:shadow-[0_18px_55px_rgba(15,23,42,.08)]"
        >
            <header
                class="sticky top-0 z-20 flex min-h-[68px] items-center justify-between border-b border-slate-200/80 bg-white/85 px-5 backdrop-blur-xl sm:px-7"
            >
                <div class="flex items-center gap-3">
                    <button
                        aria-label="Buka menu navigasi"
                        :aria-expanded="open"
                        class="grid size-10 place-items-center rounded-xl border border-stone-200 bg-white text-lg lg:hidden"
                        @click="open = true"
                    >
                        ☰
                    </button>
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-lime-700"
                        >
                            {{ currentOutlet?.name || "All outlets" }}
                        </p>
                        <h1
                            class="mt-0.5 text-lg font-semibold tracking-tight text-stone-950"
                        >
                            <slot name="title">Dashboard</slot>
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span
                        class="hidden rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 md:block"
                        >System online</span
                    ><select
                        v-if="user?.role === 'admin'"
                        aria-label="Pilih outlet aktif"
                        class="hidden rounded-xl border-slate-200 bg-white text-xs shadow-sm sm:block"
                        @change="selectOutlet"
                    >
                        <option value="">Semua outlet</option>
                        <option
                            v-for="(name, id) in outlets"
                            :key="id"
                            :value="id"
                            :selected="currentOutlet?.id === Number(id)"
                        >
                            {{ name }}
                        </option>
                    </select>
                    <div class="relative">
                        <button
                            aria-label="Notifikasi"
                            class="relative grid size-9 place-items-center rounded-xl border border-slate-200 bg-white text-sm"
                            @click="notificationOpen = !notificationOpen"
                        >
                            ●<span
                                v-if="notifications.unread_count"
                                class="absolute -right-1 -top-1 min-w-4 rounded-full bg-rose-600 px-1 text-[9px] font-bold leading-4 text-white"
                                >{{ notifications.unread_count }}</span
                            >
                        </button>
                        <div
                            v-if="notificationOpen"
                            class="absolute right-0 top-11 z-50 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                        >
                            <div
                                class="flex items-center justify-between border-b px-4 py-3"
                            >
                                <strong class="text-sm">Notifikasi</strong
                                ><button
                                    v-if="notifications.unread_count"
                                    class="text-xs text-cyan-700"
                                    @click="readAllNotifications"
                                >
                                    Tandai dibaca
                                </button>
                            </div>
                            <button
                                v-for="notification in notifications.items"
                                :key="notification.id"
                                class="block w-full border-b px-4 py-3 text-left hover:bg-slate-50"
                                :class="{
                                    'bg-cyan-50/60': !notification.read_at,
                                }"
                                @click="readNotification(notification)"
                            >
                                <strong class="block text-xs text-slate-900">{{
                                    notification.data.title
                                }}</strong
                                ><span
                                    class="mt-1 block text-xs text-slate-500"
                                    >{{ notification.data.message }}</span
                                ><span
                                    class="mt-1 block text-[10px] text-slate-400"
                                    >{{ notification.created_at }}</span
                                >
                            </button>
                            <p
                                v-if="!notifications.items.length"
                                class="p-5 text-center text-xs text-slate-500"
                            >
                                Belum ada notifikasi.
                            </p>
                        </div>
                    </div>
                    <div class="hidden h-8 w-px bg-slate-200 sm:block" />
                    <div
                        class="grid size-9 place-items-center rounded-xl bg-slate-900 text-xs font-bold text-cyan-300"
                    >
                        {{ user?.name?.slice(0, 1) }}
                    </div>
                    <button
                        aria-label="Keluar dari aplikasi"
                        class="hidden text-sm font-medium text-slate-500 transition hover:text-rose-600 sm:block"
                        @click="logout"
                    >
                        Keluar
                    </button>
                </div>
            </header>
            <ToastStack
                :success="flash.success"
                :error="flash.error"
                :errors="errors"
            />
            <div class="p-5 sm:p-7"><slot /></div>
        </main>
    </div>
</template>
