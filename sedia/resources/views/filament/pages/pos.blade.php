<x-filament-panels::page>
    <style>
        .pos-page { --pos-radius: 1rem; }
        .pos-toolbar { display:flex; flex-wrap:wrap; gap:0.75rem; align-items:end; }
        .pos-toolbar > * { flex: 1 1 160px; }
        .pos-toolbar .pos-search { flex: 2 1 260px; }
        .pos-pay-pills { display:flex; gap:0.5rem; flex-wrap:wrap; }
        .pos-pay-pill { padding:0.5rem 0.9rem; border-radius:9999px; border:1px solid #e5e7eb; background:#fff; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 150ms; }
        .pos-pay-pill.active { background:var(--primary-600, #d97706); color:#fff; border-color:var(--primary-600, #d97706); box-shadow:0 2px 8px rgba(245,158,11,0.3); }
        .dark .pos-pay-pill { background:#1f2937; border-color:#374151; color:#e5e7eb; }
        .dark .pos-pay-pill.active { background:#f59e0b; color:#111827; border-color:#f59e0b; }
        .pos-cat-pills { display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.9rem; }
        .pos-cat-pill { padding:0.35rem 0.75rem; border-radius:9999px; border:1px solid #e5e7eb; background:#f9fafb; font-size:0.78rem; font-weight:500; cursor:pointer; }
        .pos-cat-pill.active { background:#111827; color:#fff; border-color:#111827; }
        .dark .pos-cat-pill { background:#111827; border-color:#374151; color:#9ca3af; }
        .dark .pos-cat-pill.active { background:#fff; color:#111827; border-color:#fff; }
        .pos-layout { display:grid; grid-template-columns:1fr; gap:1rem; margin-top:1rem; }
        @media (min-width: 1024px) { .pos-layout { grid-template-columns: minmax(0, 1.7fr) 380px; align-items:start; } }
        .pos-menu-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:0.85rem; }
        @media (min-width: 640px) { .pos-menu-grid { grid-template-columns:repeat(3, minmax(0,1fr)); } }
        @media (min-width: 1280px) { .pos-menu-grid { grid-template-columns:repeat(4, minmax(0,1fr)); } }
        .pos-menu-card { position:relative; border:1px solid #e5e7eb; border-radius:1rem; padding:1rem; background:#fff; cursor:pointer; transition:all 150ms; text-align:left; display:flex; flex-direction:column; min-height:118px; }
        .pos-menu-card:hover { border-color:#f59e0b; box-shadow:0 4px 16px rgba(0,0,0,0.07); transform:translateY(-1px); }
        .pos-menu-card:active { transform:translateY(0); }
        .dark .pos-menu-card { background:#1f2937; border-color:#2d3748; color:#f3f4f6; }
        .dark .pos-menu-card:hover { background:#1e293b; border-color:#f59e0b; }
        .pos-menu-cat { font-size:0.68rem; letter-spacing:0.06em; text-transform:uppercase; font-weight:700; color:#9ca3af; }
        .pos-menu-name { font-size:0.92rem; font-weight:700; line-height:1.3; margin-top:0.3rem; color:#111827; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.4em; }
        .dark .pos-menu-name { color:#f9fafb; }
        .pos-menu-price { margin-top:auto; padding-top:0.7rem; font-size:0.92rem; font-weight:800; color:#b45309; }
        .dark .pos-menu-price { color:#fbbf24; }
        .pos-menu-add { position:absolute; top:0.7rem; right:0.7rem; width:26px; height:26px; border-radius:9999px; background:#111827; color:#fff; display:flex; align-items:center; justify-content:center; font-size:14px; line-height:1; }
        .dark .pos-menu-add { background:#f59e0b; color:#111827; }
        .pos-cart { position:sticky; top:1rem; display:flex; flex-direction:column; max-height: calc(100vh - 2rem); }
        .pos-cart-items { flex:1; overflow:auto; margin: -0.25rem; padding:0.25rem; }
        .pos-cart-row { display:flex; gap:0.75rem; align-items:center; padding:0.75rem 0; border-bottom:1px solid #f3f4f6; }
        .dark .pos-cart-row { border-bottom-color:#1f2937; }
        .pos-cart-row:last-child { border-bottom:none; }
        .pos-cart-name { font-size:0.86rem; font-weight:600; color:#111827; line-height:1.3; }
        .dark .pos-cart-name { color:#f3f4f6; }
        .pos-cart-meta { font-size:0.75rem; color:#6b7280; margin-top:0.15rem; }
        .pos-qty { display:flex; align-items:center; gap:0.35rem; background:#f9fafb; border-radius:9999px; padding:0.2rem; }
        .dark .pos-qty { background:#111827; }
        .pos-qty-btn { width:28px; height:28px; border-radius:9999px; border:none; background:#fff; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-weight:700; box-shadow:0 1px 2px rgba(0,0,0,0.08); transition:all 120ms; }
        .pos-qty-btn:hover { background:#111827; color:#fff; }
        .dark .pos-qty-btn { background:#1f2937; color:#e5e7eb; }
        .pos-qty-val { width:1.6rem; text-align:center; font-size:0.85rem; font-weight:700; }
        .pos-total-box { background:#f9fafb; border-radius:0.85rem; padding:0.9rem 1rem; margin-top:0.75rem; }
        .dark .pos-total-box { background:#0f172a; }
    </style>

    <div class="pos-page">
        {{-- Toolbar --}}
        <x-filament::section>
            <div class="pos-toolbar">
                @if ($this->isAdminUser())
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Outlet</label>
                        <select wire:model.live="outletId" class="fi-input mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bayar</label>
                    @if (! $isSplit)
                        <div class="pos-pay-pills mt-1">
                            @foreach (['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debit' => 'Debit'] as $val => $label)
                                <button type="button" wire:click="$set('paymentMethod','{{ $val }}')" class="pos-pay-pill {{ $paymentMethod === $val ? 'active' : '' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            @foreach (['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'] as $k=>$lbl)
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500">{{ $lbl }}</label>
                                    <input type="number" wire:model.live.debounce.300ms="splitAmounts.{{ $k }}" min="0" step="1000" class="fi-input mt-0.5 w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="0">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="mt-2 flex items-center gap-1.5 text-xs"><input type="checkbox" wire:model.live="isSplit"> Split payment</label>
                </div>
                <div class="pos-search">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cari menu</label>
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input wire:model.live.debounce.300ms="search" placeholder="Ketik nama atau kategori..." class="fi-input w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm dark:border-gray-700 dark:bg-gray-900" />
                    </div>
                </div>
            </div>

            <div class="pos-cat-pills">
                <button wire:click="$set('selectedCategory','')" class="pos-cat-pill {{ $selectedCategory === '' ? 'active' : '' }}">Semua</button>
                @foreach ($this->categories as $cat)
                    <button wire:click="$set('selectedCategory','{{ $cat }}')" class="pos-cat-pill {{ $selectedCategory === $cat ? 'active' : '' }}">{{ $cat }}</button>
                @endforeach
            </div>
        </x-filament::section>

        <div class="pos-layout">
            {{-- Menu grid --}}
            <x-filament::section>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold tracking-tight">Menu</h3>
                    <span class="rounded-full bg-gray-900 px-2.5 py-1 text-xs font-semibold text-white dark:bg-white dark:text-gray-900">{{ $this->menuItems->count() }} item</span>
                </div>
                <div class="pos-menu-grid">
                    @forelse ($this->menuItems as $menu)
                        <button wire:click="addToCart({{ $menu->id }})" class="pos-menu-card" wire:key="menu-{{ $menu->id }}">
                            <span class="pos-menu-add">+</span>
                            <span class="pos-menu-cat">{{ $menu->category ?? 'Tanpa kategori' }}</span>
                            <span class="pos-menu-name">{{ $menu->name }}</span>
                            <span class="pos-menu-price">{{ $this->formatRupiah($menu->price) }}</span>
                        </button>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-gray-200 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-800/50">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada menu</div>
                            <div class="mt-1 text-xs text-gray-500">Coba ubah pencarian atau kategori.</div>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            {{-- Cart --}}
            <x-filament::section class="pos-cart">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold tracking-tight">Keranjang</h3>
                    <span class="rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-white">{{ count($cart) }} item</span>
                </div>

                <div class="pos-cart-items">
                    @forelse ($cart as $i => $row)
                        <div class="pos-cart-row" wire:key="cart-{{ $i }}-{{ $row['menu_item_id'] }}">
                            <div class="min-w-0 flex-1">
                                <div class="pos-cart-name truncate">{{ $row['name'] }}</div>
                                <div class="pos-cart-meta">{{ $this->formatRupiah($row['price']) }} · Subtotal <span class="font-semibold text-gray-900 dark:text-white">{{ $this->formatRupiah($row['subtotal']) }}</span></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="pos-qty">
                                    <button wire:click="decQty({{ $i }})" class="pos-qty-btn" aria-label="Kurangi">−</button>
                                    <span class="pos-qty-val">{{ $row['qty'] }}</span>
                                    <button wire:click="incQty({{ $i }})" class="pos-qty-btn" aria-label="Tambah">+</button>
                                </div>
                                <button wire:click="removeFromCart({{ $i }})" class="rounded-full p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-800/50">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm dark:bg-gray-900">🛒</div>
                            <div class="mt-3 text-sm font-semibold">Keranjang kosong</div>
                            <div class="mt-1 text-xs text-gray-500">Tap kartu menu untuk menambah pesanan.</div>
                        </div>
                    @endforelse
                </div>

                <div class="pos-total-box">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total bayar</span>
                        <span class="text-xs text-gray-500">{{ count($cart) }} item</span>
                    </div>
                    <div class="mt-1 text-2xl font-extrabold tracking-tight">{{ $this->formatRupiah($this->cartTotal) }}</div>
                    @if ($isSplit)
                        <div class="mt-2 space-y-1 text-xs">
                            <div class="flex justify-between"><span>Dibayar</span><span class="font-semibold">{{ $this->formatRupiah($this->paidTotal) }}</span></div>
                            <div class="flex justify-between text-amber-700 dark:text-amber-400"><span>Kembalian</span><span class="font-bold">{{ $this->formatRupiah($this->changeDue) }}</span></div>
                        </div>
                    @endif
                    <div class="mt-1 text-xs text-gray-500">Metode: <span class="font-semibold text-gray-900 dark:text-white">{{ $isSplit ? 'SPLIT' : strtoupper($paymentMethod) }}</span> · Outlet: <span class="font-semibold">{{ $this->outletId ? ($this->outletOptions()[$outletId] ?? '-') : '-' }}</span></div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <x-filament::button wire:click="clearCart" color="gray" icon="heroicon-o-trash" :disabled="empty($cart)">Kosongkan</x-filament::button>
                    <x-filament::button wire:click="checkout" icon="heroicon-o-credit-card" :disabled="empty($cart)" style="background: #f59e0b; border-color:#f59e0b;">Bayar</x-filament::button>
                </div>
                <p class="mt-2.5 text-center text-[11px] leading-relaxed text-gray-500">Stok bahan dipotong otomatis saat bayar. Jika stok kurang, transaksi dibatalkan dengan notifikasi.</p>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
