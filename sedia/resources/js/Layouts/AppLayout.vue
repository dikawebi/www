<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage<any>();
const open = ref(false);
const user = computed(() => page.props.auth?.user);
const currentOutlet = computed(() => page.props.auth?.currentOutlet);
const outlets = computed(() => page.props.auth?.outlets ?? {});
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const loading = ref(false);

const navGroups = [
    { label: 'Ringkasan', items: [{ label: 'Dashboard', href: '/app', icon: '✦' }] },
    { label: 'Penjualan', items: [{ label: 'POS Kasir', href: '/app/pos', icon: '＋' }, { label: 'Transaksi', href: '/app/transactions', icon: '▤' }] },
    { label: 'Persediaan', items: [{ label: 'Master Data', href: '/app/master/menus', icon: '◈' }, { label: 'Persediaan', href: '/app/stock', icon: '◇' }, { label: 'Transfer & Opname', href: '/app/stock-transfers', icon: '⇄' }] },
    { label: 'Operasional', items: [{ label: 'Penggajian', href: '/app/operations/payrolls', icon: '◫' }, { label: 'Kasbon', href: '/app/operations/kasbons', icon: '◎' }, { label: 'Tutup Kasir', href: '/app/operations/closing', icon: '◷' }, { label: 'Reorder', href: '/app/operations/reorder', icon: '↻' }] },
    { label: 'Insight', items: [{ label: 'Laporan', href: '/app/reports/sales-by-outlet', icon: '⌁' }, { label: 'Activity Log', href: '/app/operations/activity-logs', icon: '☷' }] },
    { label: 'Konfigurasi', items: [{ label: 'Branding', href: '/app/settings/branding', icon: '◉' }, { label: 'Permission', href: '/app/settings/permissions', icon: '⚙' }] },
];

function active(href: string) { return href === '/app' ? page.url === '/app' : page.url.startsWith(href.split('/').slice(0, 4).join('/')); }
function selectOutlet(event: Event) { router.post('/dashboard/outlet-context', { selected_outlet_id: (event.target as HTMLSelectElement).value || null }); }
function logout() { router.post('/app/logout'); }
router.on('start', () => { loading.value = true; });
router.on('finish', () => { loading.value = false; });
</script>

<template>
    <div class="spectrum-shell min-h-screen bg-[#f6f7f4] text-stone-900 lg:flex lg:gap-3 lg:p-3" :data-design="page.props.design || 'lime'">
        <div v-if="loading" class="fixed inset-x-0 top-0 z-[100] h-1 bg-[#d8f36a] shadow-[0_0_18px_#d8f36a]" />
        <div v-if="open" class="fixed inset-0 z-30 bg-stone-950/40 backdrop-blur-sm lg:hidden" @click="open = false" />
        <aside class="spectrum-sidebar fixed inset-y-0 left-0 z-40 flex w-[280px] -translate-x-full flex-col bg-[#101a2f] px-4 py-5 text-stone-200 shadow-2xl transition-transform lg:static lg:inset-auto lg:translate-x-0 lg:rounded-[1.4rem] lg:shadow-[0_20px_50px_rgba(15,23,42,.18)]" :class="{ 'translate-x-0': open }">
            <div class="mb-7 flex items-center gap-3 px-2">
                <div class="grid size-10 place-items-center rounded-xl bg-[#22d3ee] text-base font-black text-slate-950 shadow-[0_0_0_5px_rgba(34,211,238,0.1)]">S</div>
                <div><p class="font-semibold tracking-tight text-white">Sedia</p><p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Operation suite</p></div>
            </div>
            <nav class="space-y-5 overflow-y-auto pr-1">
                <section v-for="group in navGroups" :key="group.label">
                    <p class="mb-1.5 px-3 text-[9px] font-bold uppercase tracking-[0.24em] text-slate-600">{{ group.label }}</p>
                    <div class="space-y-0.5"><Link v-for="item in group.items" :key="item.href" :href="item.href" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] transition" :class="active(item.href) ? 'bg-[#22d3ee] font-semibold text-slate-950 shadow-lg shadow-cyan-950/20' : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'" @click="open = false"><span class="grid size-7 place-items-center rounded-lg text-xs" :class="active(item.href) ? 'bg-slate-950/10' : 'bg-white/[0.05] text-slate-500 group-hover:text-slate-200'">{{ item.icon }}</span>{{ item.label }}</Link></div>
                </section>
            </nav>
            <div class="mt-auto rounded-xl border border-white/[0.08] bg-white/[0.05] p-3"><p class="text-[9px] font-bold uppercase tracking-[0.2em] text-slate-600">Signed in as</p><p class="mt-1.5 truncate text-sm font-medium text-white">{{ user?.name }}</p><p class="mt-0.5 text-xs capitalize text-slate-500">{{ user?.role }}</p></div>
        </aside>
        <main class="spectrum-workspace min-w-0 flex-1 bg-white/55 lg:overflow-hidden lg:rounded-[1.4rem] lg:border lg:border-white/80 lg:shadow-[0_18px_55px_rgba(15,23,42,.08)]">
            <header class="sticky top-0 z-20 flex min-h-[68px] items-center justify-between border-b border-slate-200/80 bg-white/85 px-5 backdrop-blur-xl sm:px-7">
                <div class="flex items-center gap-3"><button class="grid size-10 place-items-center rounded-xl border border-stone-200 bg-white text-lg lg:hidden" @click="open = true">☰</button><div><p class="text-[10px] font-bold uppercase tracking-[0.22em] text-lime-700">{{ currentOutlet?.name || 'All outlets' }}</p><h1 class="mt-0.5 text-lg font-semibold tracking-tight text-stone-950"><slot name="title">Dashboard</slot></h1></div></div>
                <div class="flex items-center gap-2 sm:gap-3"><span class="hidden rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 md:block">System online</span><select v-if="user?.role === 'admin'" class="hidden rounded-xl border-slate-200 bg-white text-xs shadow-sm sm:block" @change="selectOutlet"><option value="">Semua outlet</option><option v-for="(name, id) in outlets" :key="id" :value="id" :selected="currentOutlet?.id === Number(id)">{{ name }}</option></select><div class="hidden h-8 w-px bg-slate-200 sm:block"/><div class="grid size-9 place-items-center rounded-xl bg-slate-900 text-xs font-bold text-cyan-300">{{ user?.name?.slice(0, 1) }}</div><button class="hidden text-sm font-medium text-slate-500 transition hover:text-rose-600 sm:block" @click="logout">Keluar</button></div>
            </header>
            <div v-if="flash.success" class="fixed right-5 top-20 z-50 rounded-2xl bg-stone-900 px-5 py-3 text-sm font-medium text-white shadow-2xl">{{ flash.success }}</div>
            <div v-if="flash.error" class="fixed right-5 top-20 z-50 max-w-sm rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-medium text-rose-700 shadow-2xl">{{ flash.error }}</div>
            <div v-if="Object.keys(errors).length" class="fixed right-5 top-20 z-50 max-w-sm rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700 shadow-xl"><p class="font-semibold">Periksa kembali input Anda.</p><p v-for="(message, key) in errors" :key="key" class="mt-1">{{ message }}</p></div>
            <div class="p-5 sm:p-7"><slot /></div>
        </main>
    </div>
</template>
