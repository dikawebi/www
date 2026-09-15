<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage<any>();
const search = ref('');
const selectedCategory = ref('Semua');
const paymentMethod = ref('cash');
const paidAmount = ref(0);
const outletId = ref(page.props.defaultOutletId ?? '');
const processing = ref(false);
const localError = ref('');
const cart = reactive<Record<number, number>>({});
const menus = computed(() => page.props.menus ?? []);
const categories = computed(() => ['Semua', ...new Set(menus.value.map((menu: any) => menu.category).filter(Boolean))]);
const filteredMenus = computed(() => menus.value.filter((menu: any) => (selectedCategory.value === 'Semua' || menu.category === selectedCategory.value) && menu.name.toLowerCase().includes(search.value.trim().toLowerCase())));
const cartRows = computed(() => menus.value.filter((menu: any) => cart[menu.id]).map((menu: any) => ({ ...menu, quantity: cart[menu.id], subtotal: Number(menu.price) * cart[menu.id] })));
const itemCount = computed(() => cartRows.value.reduce((sum: number, row: any) => sum + row.quantity, 0));
const total = computed(() => cartRows.value.reduce((sum: number, row: any) => sum + row.subtotal, 0));
const change = computed(() => Math.max(0, Number(paidAmount.value || 0) - total.value));
const checkoutError = computed(() => page.props.errors?.checkout ?? page.props.errors?.items ?? page.props.errors?.paid_amount ?? page.props.errors?.outlet_id ?? localError.value);
const canCheckout = computed(() => Boolean(outletId.value) && cartRows.value.length > 0 && Number(paidAmount.value) >= total.value && !processing.value);
const rupiah = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);

watch([total, paymentMethod], () => { paidAmount.value = total.value; localError.value = ''; });
function add(menu: any) { if ((cart[menu.id] ?? 0) < 999) cart[menu.id] = (cart[menu.id] ?? 0) + 1; }
function decrement(menu: any) { if ((cart[menu.id] ?? 0) <= 1) delete cart[menu.id]; else cart[menu.id]--; }
function remove(menu: any) { delete cart[menu.id]; }
function clearCart() { Object.keys(cart).forEach((id) => delete cart[Number(id)]); localError.value = ''; }
function checkout() {
    localError.value = '';
    if (!outletId.value) localError.value = 'Pilih outlet sebelum checkout.';
    else if (!cartRows.value.length) localError.value = 'Tambahkan minimal satu menu ke keranjang.';
    else if (Number(paidAmount.value) < total.value) localError.value = 'Jumlah pembayaran masih kurang.';
    if (localError.value) return;
    router.post('/app/pos/checkout', { outlet_id: outletId.value, payment_method: paymentMethod.value, paid_amount: paidAmount.value, items: cartRows.value.map((row: any) => ({ menu_item_id: row.id, quantity: row.quantity })) }, { preserveScroll: true, onStart: () => { processing.value = true; }, onSuccess: clearCart, onFinish: () => { processing.value = false; } });
}
</script>

<template>
    <Head title="POS"/>
    <AppLayout>
        <template #title>POS Kasir</template>
        <div class="mx-auto grid max-w-[1500px] gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
            <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500"></span><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-700">Kasir aktif</p></div><h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Pilih menu</h2></div><select v-if="page.props.auth?.user?.role === 'admin'" v-model="outletId" class="rounded-xl border-slate-200 bg-slate-50 text-sm"><option value="" disabled>Pilih outlet</option><option v-for="(name, id) in page.props.outlets" :key="id" :value="Number(id)">{{ name }}</option></select></div>
                <div class="mb-5 space-y-3"><div class="relative"><span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">⌕</span><input v-model="search" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm" placeholder="Cari nama menu..."></div><div class="flex gap-2 overflow-x-auto pb-1"><button v-for="category in categories" :key="category" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold transition" :class="selectedCategory === category ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-900'" @click="selectedCategory = category">{{ category }}</button></div></div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><button v-for="menu in filteredMenus" :key="menu.id" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-lg hover:shadow-cyan-950/5" @click="add(menu)"><div class="flex items-start justify-between gap-3"><span class="rounded-md bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500">{{ menu.category || 'Menu' }}</span><span v-if="cart[menu.id]" class="rounded-full bg-cyan-500 px-2 py-0.5 text-[10px] font-bold text-slate-950">{{ cart[menu.id] }} di keranjang</span></div><div class="mt-8 flex items-end justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ menu.name }}</p><p class="mt-1 text-sm font-bold text-cyan-700">{{ rupiah(Number(menu.price)) }}</p></div><span class="grid size-9 shrink-0 place-items-center rounded-lg bg-cyan-50 font-bold text-cyan-700 transition group-hover:bg-cyan-500 group-hover:text-slate-950">+</span></div></button><div v-if="!filteredMenus.length" class="col-span-full rounded-xl border border-dashed border-slate-300 py-14 text-center"><p class="text-sm font-medium text-slate-600">Menu tidak ditemukan</p><p class="mt-1 text-xs text-slate-400">Coba kata kunci atau kategori lain.</p></div></div>
            </section>

            <aside class="h-fit overflow-hidden rounded-2xl bg-slate-950 text-white shadow-xl shadow-slate-950/15 xl:sticky xl:top-5"><div class="border-b border-white/10 p-5"><div class="flex items-center justify-between"><div><p class="text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-400">Pesanan saat ini</p><h2 class="mt-1 text-lg font-semibold">Keranjang</h2></div><button v-if="cartRows.length" class="text-xs text-slate-500 transition hover:text-rose-400" @click="clearCart">Kosongkan</button></div><div class="mt-3 flex gap-2 text-xs"><span class="rounded-lg bg-white/[0.07] px-2.5 py-1 text-slate-300">{{ itemCount }} produk</span><span class="rounded-lg bg-white/[0.07] px-2.5 py-1 text-slate-300">{{ cartRows.length }} menu</span></div></div>
                <div class="max-h-[340px] min-h-[150px] space-y-3 overflow-y-auto p-5"><div v-for="row in cartRows" :key="row.id" class="rounded-xl bg-white/[0.06] p-3"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{{ row.name }}</p><p class="mt-1 text-xs text-slate-500">{{ rupiah(Number(row.price)) }} / item</p></div><button class="text-xs text-slate-600 hover:text-rose-400" title="Hapus" @click="remove(row)">×</button></div><div class="mt-3 flex items-center justify-between"><div class="flex items-center gap-2"><button class="grid size-7 place-items-center rounded-lg bg-white/10 hover:bg-white/20" @click="decrement(row)">−</button><span class="w-6 text-center text-sm font-semibold">{{ row.quantity }}</span><button class="grid size-7 place-items-center rounded-lg bg-white/10 hover:bg-white/20" @click="add(row)">+</button></div><strong class="text-sm text-cyan-300">{{ rupiah(row.subtotal) }}</strong></div></div><div v-if="!cartRows.length" class="grid min-h-[150px] place-items-center text-center"><div><span class="mx-auto grid size-11 place-items-center rounded-full bg-white/[0.06] text-slate-500">＋</span><p class="mt-3 text-sm text-slate-400">Keranjang masih kosong</p><p class="mt-1 text-xs text-slate-600">Pilih menu dari katalog.</p></div></div></div>
                <div class="border-t border-white/10 bg-white/[0.03] p-5"><div v-if="checkoutError" class="mb-4 rounded-xl border border-rose-400/20 bg-rose-500/10 p-3 text-xs leading-5 text-rose-200"><strong class="block text-rose-300">Checkout belum berhasil</strong>{{ checkoutError }}</div><div class="mb-4 flex items-end justify-between"><span class="text-sm text-slate-400">Total pembayaran</span><strong class="text-xl text-white">{{ rupiah(total) }}</strong></div><div class="grid grid-cols-3 gap-2"><button v-for="method in [{ id: 'cash', label: 'Tunai' }, { id: 'qris', label: 'QRIS' }, { id: 'transfer', label: 'Transfer' }]" :key="method.id" class="rounded-lg px-2 py-2 text-xs font-semibold transition" :class="paymentMethod === method.id ? 'bg-cyan-500 text-slate-950' : 'bg-white/[0.07] text-slate-400 hover:bg-white/10'" @click="paymentMethod = method.id">{{ method.label }}</button></div><label class="mt-4 block text-xs text-slate-400">Jumlah dibayar<input v-model.number="paidAmount" type="number" min="0" :readonly="paymentMethod !== 'cash'" class="mt-1.5 w-full rounded-xl border border-white/10 bg-white/[0.07] text-white read-only:text-slate-400"></label><div v-if="paymentMethod === 'cash' && paidAmount >= total && total > 0" class="mt-3 flex justify-between text-xs"><span class="text-slate-500">Kembalian</span><strong class="text-emerald-400">{{ rupiah(change) }}</strong></div><button class="mt-5 flex w-full items-center justify-center rounded-xl bg-cyan-500 px-4 py-3 font-semibold text-slate-950 shadow-lg shadow-cyan-950/30 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-40" :disabled="!canCheckout" @click="checkout"><span v-if="processing" class="mr-2 size-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>{{ processing ? 'Memproses...' : 'Bayar sekarang' }}</button></div>
            </aside>
        </div>
    </AppLayout>
</template>
