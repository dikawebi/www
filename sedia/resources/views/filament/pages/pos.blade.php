<x-filament-panels::page>
    <style>
        /* ——— POS: touch-friendly & lega ——— */
        .pos-page{ --pos-gap:1rem; }
        .pos-toolbar{ display:grid; grid-template-columns:1fr; gap:1rem; }
        @media(min-width:768px){ .pos-toolbar{ grid-template-columns: 1.1fr 1fr; } }
        @media(min-width:1100px){ .pos-toolbar{ grid-template-columns: 220px 1fr 1.15fr; } }
        .pos-field label{ display:block; font-size:0.72rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#6b7280; margin-bottom:0.45rem; }
        .pos-field select, .pos-field input{ width:100%; border-radius:0.9rem; border:1px solid #e5e7eb; background:#fff; padding:0.85rem 0.95rem; font-size:0.92rem; line-height:1; transition:border-color 150ms, box-shadow 150ms; }
        .pos-field select:focus, .pos-field input:focus{ outline:none; border-color:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,0.15); }
        .dark .pos-field select, .dark .pos-field input{ background:#111827; border-color:#374151; color:#f3f4f6; }
        .pos-pay-pills{ display:flex; flex-wrap:wrap; gap:0.6rem; }
        .pos-pay-pill{ flex:1 1 auto; min-height:44px; padding:0.65rem 1rem; border-radius:9999px; border:1.5px solid #e5e7eb; background:#fff; font-size:0.86rem; font-weight:700; cursor:pointer; transition:all 150ms; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem; }
        .pos-pay-pill.active{ background:#111827; color:#fff; border-color:#111827; box-shadow:0 4px 12px rgba(0,0,0,0.12); }
        .dark .pos-pay-pill{ background:#1f2937; border-color:#334155; color:#e2e8f0; }
        .dark .pos-pay-pill.active{ background:#f59e0b; color:#111827; border-color:#f59e0b; }
        .pos-split-grid{ display:grid; grid-template-columns:1fr 1fr; gap:0.7rem; margin-top:0.6rem; }
        .pos-split-grid label{ font-size:0.7rem; font-weight:700; letter-spacing:0.05em; text-transform:uppercase; color:#6b7280; }
        .pos-split-grid input{ margin-top:0.3rem; border-radius:0.9rem; border:1px solid #e5e7eb; padding:0.85rem 0.95rem; font-size:0.92rem; width:100%; min-height:44px; }
        .dark .pos-split-grid input{ background:#1f2937; border-color:#374151; color:#fff; }
        .pos-cat-bar{ display:flex; gap:0.6rem; flex-wrap:nowrap; overflow:auto; padding:0.2rem 0 0.6rem; scrollbar-width:none; margin-top:1rem; }
        .pos-cat-bar::-webkit-scrollbar{ display:none; }
        .pos-cat-pill{ flex:0 0 auto; min-height:38px; padding:0.5rem 1rem; border-radius:9999px; border:1.5px solid #e5e7eb; background:#f9fafb; font-size:0.84rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:all 150ms; }
        .pos-cat-pill.active{ background:#f59e0b; color:#fff; border-color:#f59e0b; box-shadow:0 2px 10px rgba(245,158,11,0.25); }
        .dark .pos-cat-pill{ background:#1e293b; border-color:#334155; color:#94a3b8; }
        .dark .pos-cat-pill.active{ background:#f59e0b; color:#111827; border-color:#f59e0b; }
        .pos-layout{ display:grid; grid-template-columns:1fr; gap:20px; margin-top:0; }
        @media(min-width:1050px){ .pos-layout{ grid-template-columns: minmax(0, 1.75fr) 400px; align-items:start; } }
        .pos-menu-head{ display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:0.9rem; }
        .pos-menu-head h3{ font-size:0.95rem; font-weight:800; letter-spacing:-0.02em; }
        .pos-menu-count{ background:#111827; color:#fff; border-radius:9999px; padding:0.3rem 0.7rem; font-size:0.75rem; font-weight:700; }
        .dark .pos-menu-count{ background:#f59e0b; color:#111827; }
        .pos-menu-grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:1rem; }
        @media(min-width:640px){ .pos-menu-grid{ grid-template-columns:repeat(3, minmax(0,1fr)); } }
        @media(min-width:1360px){ .pos-menu-grid{ grid-template-columns:repeat(4, minmax(0,1fr)); } }
        .pos-menu-card{ position:relative; border:1.5px solid #e5e7eb; border-radius:1.15rem; padding:1.15rem 1rem 1rem; background:#fff; cursor:pointer; transition:all 160ms; text-align:left; display:flex; flex-direction:column; min-height:148px; }
        .pos-menu-card:hover{ border-color:#f59e0b; box-shadow:0 8px 24px rgba(0,0,0,0.08); transform:translateY(-2px); }
        .pos-menu-card:active{ transform:translateY(0); box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .dark .pos-menu-card{ background:#1e293b; border-color:#334155; }
        .dark .pos-menu-card:hover{ border-color:#f59e0b; background:#1e293b; }
        .pos-menu-top{ display:flex; align-items:start; justify-content:space-between; gap:0.5rem; }
        .pos-menu-cat{ font-size:0.68rem; letter-spacing:0.07em; text-transform:uppercase; font-weight:800; color:#fff; background:#111827; padding:0.22rem 0.5rem; border-radius:9999px; line-height:1; }
        .dark .pos-menu-cat{ background:#334155; color:#e2e8f0; }
        .pos-menu-add{ width:36px; height:36px; border-radius:9999px; background:#f59e0b; color:#111827; display:grid; place-items:center; font-size:18px; font-weight:800; line-height:1; flex:0 0 36px; box-shadow:0 2px 8px rgba(245,158,11,0.35); }
        .pos-menu-name{ font-size:0.96rem; font-weight:800; line-height:1.32; margin-top:0.75rem; color:#111827; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.6em; }
        .dark .pos-menu-name{ color:#f1f5f9; }
        .pos-menu-foot{ margin-top:auto; padding-top:0.85rem; display:flex; align-items:baseline; justify-content:space-between; gap:0.5rem; }
        .pos-menu-price{ font-size:1rem; font-weight:900; color:#b45309; letter-spacing:-0.02em; }
        .dark .pos-menu-price{ color:#fcd34d; }
        .pos-menu-unit{ font-size:0.72rem; color:#9ca3af; font-weight:600; }
        .pos-cart{ position:sticky; top:1rem; display:flex; flex-direction:column; max-height:calc(100vh - 1.5rem); }
        @media(max-width:1049px){ .pos-cart{ position:static; max-height:none; } }
        .pos-cart-head{ display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding-bottom:0.85rem; border-bottom:1px solid #f3f4f6; margin-bottom:0.25rem; }
        .dark .pos-cart-head{ border-bottom-color:#1e293b; }
        .pos-cart-title{ font-size:0.95rem; font-weight:800; letter-spacing:-0.02em; }
        .pos-cart-badge{ background:#f59e0b; color:#fff; border-radius:9999px; padding:0.3rem 0.7rem; font-size:0.75rem; font-weight:800; }
        .pos-cart-items{ flex:1; overflow:auto; padding:0.25rem; margin:0 -0.25rem; }
        @media(max-width:1049px){ .pos-cart-items{ max-height:420px; } }
        .pos-cart-row{ display:grid; grid-template-columns:1fr auto; gap:0.75rem; align-items:center; padding:0.95rem 0.6rem; border-bottom:1px solid #f3f4f6; border-radius:0.75rem; transition:background 120ms; }
        .pos-cart-row:hover{ background:#f9fafb; }
        .dark .pos-cart-row{ border-bottom-color:#1e293b; }
        .dark .pos-cart-row:hover{ background:#0f172a; }
        .pos-cart-name{ font-size:0.9rem; font-weight:700; color:#111827; line-height:1.35; }
        .dark .pos-cart-name{ color:#f1f5f9; }
        .pos-cart-meta{ font-size:0.78rem; color:#6b7280; margin-top:0.2rem; display:flex; gap:0.5rem; flex-wrap:wrap; }
        .pos-cart-meta strong{ color:#111827; }
        .dark .pos-cart-meta strong{ color:#f1f5f9; }
        .pos-qty{ display:flex; align-items:center; gap:0.4rem; background:#fff; border:1px solid #e5e7eb; border-radius:9999px; padding:0.25rem; }
        .dark .pos-qty{ background:#1f2937; border-color:#334155; }
        .pos-qty-btn{ width:36px; height:36px; border-radius:9999px; border:none; background:#111827; color:#fff; display:grid; place-items:center; cursor:pointer; font-size:16px; font-weight:800; line-height:1; transition:all 140ms; }
        .pos-qty-btn:hover{ background:#000; transform:scale(1.05); }
        .pos-qty-btn:active{ transform:scale(0.96); }
        .dark .pos-qty-btn{ background:#f59e0b; color:#111827; }
        .pos-qty-val{ min-width:2rem; text-align:center; font-size:0.92rem; font-weight:800; }
        .pos-remove{ width:36px; height:36px; border-radius:9999px; border:1px solid #fee2e2; background:#fff; color:#dc2626; display:grid; place-items:center; cursor:pointer; transition:all 140ms; }
        .pos-remove:hover{ background:#dc2626; color:#fff; border-color:#dc2626; }
        .dark .pos-remove{ background:#1f2937; border-color:#7f1d1d; color:#fca5a5; }
        .pos-total-box{ background:#111827; color:#fff; border-radius:1rem; padding:1.15rem; margin-top:0.9rem; }
        .dark .pos-total-box{ background:#f59e0b; color:#111827; }
        .pos-total-label{ font-size:0.7rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:700; opacity:0.7; }
        .pos-total-value{ font-size:1.75rem; font-weight:900; letter-spacing:-0.03em; line-height:1; margin-top:0.35rem; }
        .pos-total-meta{ font-size:0.78rem; opacity:0.75; margin-top:0.5rem; display:flex; flex-wrap:wrap; gap:0.4rem 1rem; }
        .pos-actions{ display:grid; grid-template-columns:1fr 1.35fr; gap:0.75rem; margin-top:0.9rem; }
        .pos-actions .fi-btn{ min-height:48px; font-size:0.92rem; font-weight:700; border-radius:0.9rem; }
        .pos-empty{ border:1.5px dashed #e5e7eb; border-radius:1rem; background:#f9fafb; padding:2.2rem 1.5rem; text-align:center; }
        .dark .pos-empty{ background:#0f172a; border-color:#1e293b; }
        .dark #cashPayDialog form, .dark #posSuccessDialog > div { background:#1f2937 !important; border-color:#334155 !important; }
        .dark #cashPayDialog form h3, .dark #cashPayDialog form label { color:#f1f5f9 !important; }
        .dark #cashPayDialog input { background:#111827 !important; color:#f1f5f9 !important; border-color:#374151 !important; }
        .dark #cashPayDialog button[style*="background:#fff"], .dark #cashPayDialog button[style*="background:#f8fafc"] { color:#0f172a !important; }
        .dark #cashPayDialog form span[style*="color:#475569"] { color:#94a3b8 !important; }
    </style>

    <div class="pos-page" style="display:flex; flex-direction:column; gap:20px">
        {{-- Invoice & Tanggal — 2 card terpisah, gap 20px both axes, anti-purge --}}
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px">
            {{-- Invoice: pos-menu-card --}}
            <button type="button" onclick="navigator.clipboard.writeText('{{ $previewInvoice }}'); const t=this.querySelector('.inv-copy'); if(t){const o=t.textContent; t.textContent='✓'; setTimeout(()=>t.textContent=o,1200)}" class="pos-menu-card text-left" style="min-height:132px; cursor:pointer; text-align:left; width:100%">
                <div class="pos-menu-top">
                    <span class="pos-menu-cat">INVOICE</span>
                    <span class="pos-menu-add" style="font-size:13px"><span class="inv-copy">⧉</span></span>
                </div>
                <div class="pos-menu-name" style="font-family:ui-monospace,monospace; font-size:12.5px; line-height:1.4; word-break:break-all; min-height:1.8em">{{ $previewInvoice }}</div>
                <div class="pos-menu-foot">
                    <span class="pos-menu-price" style="font-size:11.5px; color:#f59e0b">Outlet: {{ $this->outletId ? ($this->outletOptions()[$this->outletId] ?? '-') : '-' }}</span>
                    <span class="pos-menu-unit">AUTO</span>
                </div>
            </button>
            {{-- Tanggal: pos-menu-card --}}
            <div class="pos-menu-card" style="min-height:132px; cursor:default" x-data="{ now: new Date(), tick(){ this.now = new Date() } }" x-init="setInterval(()=>tick(),1000)">
                <div class="pos-menu-top">
                    <span class="pos-menu-cat">TANGGAL</span>
                    <span class="pos-menu-add" style="font-size:14px; background:#10b981">📅</span>
                </div>
                <div class="pos-menu-name" style="line-height:1.35">
                    <span style="display:block; font-size:13px; font-weight:800">{{ now()->translatedFormat('l') }}</span>
                    <span style="display:block; font-size:12px; font-weight:600; color:#94a3b8; margin-top:1px">{{ now()->translatedFormat('d F Y') }}</span>
                </div>
                <div class="pos-menu-foot">
                    <span class="pos-menu-price" style="font-size:13px" x-text="new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) + ' WIB'"> {{ now()->format('H:i') }} WIB</span>
                    <span class="pos-menu-unit" x-text="now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'})">{{ now()->format('H:i:s') }}</span>
                </div>
            </div>
        </div>

        {{-- Toolbar: outlet + bayar + cari --}}
        <x-filament::section>
            <div class="pos-toolbar">
                @if ($this->isAdminUser())
                    <div class="pos-field">
                        <label>Outlet</label>
                        <select wire:model.live="outletId">
                            @foreach ($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="pos-field">
                    <label>Metode Bayar</label>
                    @if (! $isSplit)
                        <div class="pos-pay-pills">
                            @foreach (['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debit' => 'Debit'] as $val => $label)
                                <button type="button" wire:click="$set('paymentMethod','{{ $val }}')" class="pos-pay-pill {{ $paymentMethod === $val ? 'active' : '' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @else
                        <div class="pos-split-grid">
                            @foreach (['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'] as $k=>$lbl)
                                <div>
                                    <label>{{ $lbl }}</label>
                                    <input type="number" inputmode="numeric" wire:model.live.debounce.300ms="splitAmounts.{{ $k }}" min="0" step="1000" placeholder="0">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-400" style="text-transform:none; letter-spacing:0; cursor:pointer;">
                        <input type="checkbox" wire:model.live="isSplit" style="width:16px; height:16px; accent-color:#f59e0b;"> Split payment
                    </label>
                </div>
                <div class="pos-field">
                    <label>Cari Menu</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        </span>
                        <input wire:model.live.debounce.300ms="search" placeholder="Nama menu atau kategori..." style="padding-left:2.6rem;">
                    </div>
                </div>
            </div>

            <div class="pos-cat-bar">
                <button wire:click="$set('selectedCategory','')" class="pos-cat-pill {{ $selectedCategory === '' ? 'active' : '' }}">Semua</button>
                @foreach ($this->categories as $cat)
                    <button wire:click="$set('selectedCategory','{{ $cat }}')" class="pos-cat-pill {{ $selectedCategory === $cat ? 'active' : '' }}">{{ $cat }}</button>
                @endforeach
            </div>
        </x-filament::section>

        <div class="pos-layout">
            {{-- Menu --}}
            <x-filament::section>
                <div class="pos-menu-head">
                    <h3>Menu</h3>
                    <span class="pos-menu-count">{{ $this->menuItems->count() }} item</span>
                </div>
                <div class="pos-menu-grid" wire:loading.class="opacity-60" wire:target="search, selectedCategory">
                    <div wire:loading wire:target="search, selectedCategory" class="col-span-full flex items-center justify-center gap-2 py-6 text-sm text-gray-500">
                        <svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#e5e7eb" stroke-width="3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/></svg>
                        Memuat menu...
                    </div>
                    @forelse ($this->menuItems as $menu)
                        <button wire:click="addToCart({{ $menu->id }})" wire:loading.attr="disabled" wire:target="addToCart" class="pos-menu-card" wire:key="menu-{{ $menu->id }}">
                            <div class="pos-menu-top">
                                <span class="pos-menu-cat">{{ $menu->category ?? 'Tanpa kategori' }}</span>
                                <span class="pos-menu-add">+</span>
                            </div>
                            <div class="pos-menu-name">{{ $menu->name }}</div>
                            <div class="pos-menu-foot">
                                <span class="pos-menu-price">{{ $this->formatRupiah($menu->price) }}</span>
                                <span class="pos-menu-unit">/ porsi</span>
                            </div>
                        </button>
                    @empty
                        <div wire:loading.remove wire:target="search, selectedCategory" class="pos-empty" style="grid-column:1 / -1;">
                            <div style="font-size:0.95rem; font-weight:700;">Tidak ada menu</div>
                            <div style="margin-top:0.3rem; font-size:0.84rem; color:#6b7280;">Coba ubah pencarian atau kategori.</div>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            {{-- Cart --}}
            <x-filament::section class="pos-cart">
                <div class="pos-cart-head">
                    <h3 class="pos-cart-title">Keranjang</h3>
                    <span class="pos-cart-badge">{{ count($cart) }} item · {{ array_sum(array_column($cart,'qty')) }} pcs</span>
                </div>

                <div class="pos-cart-items">
                    @forelse ($cart as $i => $row)
                        <div class="pos-cart-row" wire:key="cart-{{ $i }}-{{ $row['menu_item_id'] }}">
                            <div style="min-width:0; flex:1;">
                                <div class="pos-cart-name">{{ $row['name'] }}</div>
                                <div class="pos-cart-meta">
                                    <span>{{ $this->formatRupiah($row['price']) }} × {{ $row['qty'] }}</span>
                                    <span>·</span>
                                    <strong>{{ $this->formatRupiah($row['subtotal']) }}</strong>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <div class="pos-qty">
                                    <button wire:click="decQty({{ $i }})" class="pos-qty-btn" aria-label="Kurangi">−</button>
                                    <span class="pos-qty-val">{{ $row['qty'] }}</span>
                                    <button wire:click="incQty({{ $i }})" class="pos-qty-btn" aria-label="Tambah">+</button>
                                </div>
                                <button wire:click="removeFromCart({{ $i }})" class="pos-remove" title="Hapus" aria-label="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="pos-empty">
                            <div style="width:56px; height:56px; border-radius:9999px; background:#fff; display:grid; place-items:center; margin:0 auto; box-shadow:0 2px 8px rgba(0,0,0,0.06); font-size:22px;">🛒</div>
                            <div style="margin-top:0.9rem; font-size:0.95rem; font-weight:800;">Keranjang kosong</div>
                            <div style="margin-top:0.3rem; font-size:0.84rem; color:#6b7280; line-height:1.5;">Tap kartu menu di kiri untuk<br>menambah pesanan.</div>
                        </div>
                    @endforelse
                </div>

                <div class="pos-total-box">
                    <div class="pos-total-label">Total bayar</div>
                    <div class="pos-total-value">{{ $this->formatRupiah($this->cartTotal) }}</div>
                    @if ($isSplit)
                        <div style="margin-top:0.7rem; padding-top:0.7rem; border-top:1px solid rgba(255,255,255,0.2); display:grid; gap:0.3rem; font-size:0.84rem;">
                            <div style="display:flex; justify-content:space-between;"><span style="opacity:0.8;">Dibayar</span><span style="font-weight:700;">{{ $this->formatRupiah($this->paidTotal) }}</span></div>
                            <div style="display:flex; justify-content:space-between; font-weight:800;"><span>Kembalian</span><span>{{ $this->formatRupiah($this->changeDue) }}</span></div>
                        </div>
                    @endif
                    <div class="pos-total-meta">
                        <span>Metode: <b>{{ $isSplit ? 'SPLIT' : strtoupper($paymentMethod) }}</b></span>
                        <span>Outlet: <b>{{ $this->outletId ? ($this->outletOptions()[$outletId] ?? '-') : '-' }}</b></span>
                        <span>{{ count($cart) }} item</span>
                    </div>
                </div>

                <div class="pos-actions">
                    <x-filament::button wire:click="clearCart" color="gray" icon="heroicon-o-trash" :disabled="empty($cart)" wire:loading.attr="disabled" wire:target="checkout,clearCart" style="min-height:48px;">Kosongkan</x-filament::button>
                    @if($paymentMethod === 'cash' && ! $isSplit)
                        <x-filament::button type="button" onclick="document.getElementById('cashPayDialog')?.showModal()" icon="heroicon-o-banknotes" :disabled="empty($cart)" style="min-height:48px; background:#f59e0b; border-color:#f59e0b; color:#111827; font-weight:800;">Bayar Tunai</x-filament::button>
                    @else
                        <x-filament::button wire:click="checkout" icon="heroicon-o-credit-card" :disabled="empty($cart)" wire:loading.attr="disabled" wire:target="checkout" style="min-height:48px; background:#f59e0b; border-color:#f59e0b; color:#111827; font-weight:800;">
                            <span wire:loading.remove wire:target="checkout">Bayar</span>
                            <span wire:loading wire:target="checkout" class="inline-flex items-center gap-2"><svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="rgba(17,24,39,0.2)" stroke-width="3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="#111827" stroke-width="3" stroke-linecap="round"/></svg> Memproses...</span>
                        </x-filament::button>
                    @endif
                </div>

                {{-- Cash modal — Alpine local state + Livewire cartTotalLive agar kembalian akurat --}}
                <dialog id="cashPayDialog" wire:ignore.self x-data="{
                    cashRaw: '',
                    totalLive: $wire.entangle('cartTotalLive'),
                    get total() { return Number(this.totalLive) || 0 },
                    get cashNum() { return Number(String(this.cashRaw).replace(/\./g,'').replace(/[^0-9]/g,'')) || 0 },
                    get formattedCash() { return this.cashRaw ? new Intl.NumberFormat('id-ID').format(Number(String(this.cashRaw).replace(/\./g,'').replace(/[^0-9]/g,'')) || 0) : '' },
                    get rawChange() { return this.cashNum - this.total },
                    get change() { return Math.max(0, this.rawChange) },
                    get isShort() { return this.rawChange < 0 },
                    get isValid() { return this.rawChange >= 0 && this.total > 0 && this.cashNum > 0 },
                    formatRp(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val || 0) },
                    async submitPay() {
                        if (!this.isValid) return;
                        await $wire.set('cashReceived', this.cashNum);
                        await $wire.checkout();
                        this.cashRaw = '';
                    },
                    setCash(v) { this.cashRaw = String(v); $nextTick(() => { const el = document.getElementById('cashInputEl'); if(el) el.value = this.formattedCash }) }
                }" x-init="$watch('$wire.cartTotalLive', v => {})" @open.window="cashRaw = ''" style="border:0; padding:16px; background:transparent; max-width:440px; width:96%; margin:auto">
                    <form method="dialog" @submit.prevent="submitPay()" style="background:#fff; border-radius:20px; padding:22px; box-shadow:0 24px 60px rgba(0,0,0,0.35); border:1px solid #e5e7eb; color:#0f172a">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px">
                            <h3 style="font-size:15px; font-weight:800; margin:0">Pembayaran Tunai</h3>
                            <button type="button" onclick="this.closest('dialog').close()" style="width:30px; height:30px; border-radius:9999px; border:1px solid #e5e7eb; background:#f8fafc; cursor:pointer">✕</button>
                        </div>
                        <div style="margin-top:14px; background:#0f172a; color:#fff; border-radius:14px; padding:14px 16px; display:flex; justify-content:space-between; align-items:center">
                            <span style="font-size:12px; opacity:0.8; font-weight:600">TOTAL</span>
                            <span style="font-size:20px; font-weight:900" x-text="formatRp(total)"></span>
                        </div>
                        <div style="margin-top:14px">
                            <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:6px">Uang diterima dari pelanggan</label>
                            <input id="cashInputEl" type="text" inputmode="numeric" placeholder="Contoh: 50.000"
                                :value="formattedCash" @input="cashRaw = $event.target.value.replace(/\./g,'').replace(/[^0-9]/g,''); $event.target.value = formattedCash"
                                style="width:100%; border-radius:12px; border:1.5px solid #cbd5e1; padding:12px 14px; font-size:18px; font-weight:700; outline:none; color:#0f172a" autofocus>
                            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px">
                                <button type="button" @click="setCash(total)" style="flex:1; min-width:72px; border-radius:9999px; border:1px solid #e5e7eb; background:#f8fafc; padding:7px 10px; font-size:12px; font-weight:700; cursor:pointer">Uang pas</button>
                                @foreach([50000,100000,150000,200000] as $quick)
                                    <button type="button" @click="setCash({{ $quick }})" style="flex:1; min-width:72px; border-radius:9999px; border:1px solid #e5e7eb; background:#fff; padding:7px 10px; font-size:12px; font-weight:600; cursor:pointer">{{ number_format($quick,0,',','.') }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div style="margin-top:14px; border-radius:12px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center;"
                            :style="{ border: isShort ? '1.5px solid #fecaca' : (change > 0 ? '1.5px solid #fde68a' : '1.5px solid #e5e7eb'), background: isShort ? '#fef2f2' : (change > 0 ? '#fffbeb' : '#f8fafc') }">
                            <span style="font-size:12px; font-weight:700; color:#475569">Kembalian</span>
                            <span style="font-size:18px; font-weight:900;" :style="{ color: isShort ? '#dc2626' : '#0f172a' }" x-text="formatRp(change)"></span>
                        </div>
                        <template x-if="isShort">
                            <div style="margin-top:8px; font-size:12px; color:#dc2626; font-weight:600">
                                Uang kurang <span x-text="formatRp(total - cashNum)"></span>
                            </div>
                        </template>
                        <div style="margin-top:16px; display:grid; grid-template-columns:1fr 1fr; gap:10px">
                            <button type="button" onclick="this.closest('dialog').close()" style="border-radius:12px; border:1px solid #e5e7eb; background:#fff; padding:11px; font-size:13px; font-weight:700; cursor:pointer">Batal</button>
                            <button type="submit" :disabled="!isValid" style="border-radius:12px; border:0; padding:11px; font-size:13px; font-weight:800; cursor:pointer"
                                :style="{ background: isValid ? '#f59e0b' : '#9ca3af', color: isValid ? '#111827' : '#fff' }">
                                <span wire:loading.remove wire:target="checkout">Proses Bayar</span>
                                <span wire:loading wire:target="checkout">Memproses...</span>
                            </button>
                        </div>
                        <p style="margin-top:10px; text-align:center; font-size:11px; color:#94a3b8">Tekan Enter atau klik Proses Bayar untuk menyelesaikan</p>
                    </form>
                </dialog>
                <style>dialog::backdrop{ background:rgba(2,6,23,0.62); backdrop-filter:blur(3px) } dialog[open]{ display:grid; place-items:center; position:fixed; inset:0; width:100%; height:100%; max-width:100%; max-height:100%; z-index:9999 }</style>
                <script>
                    document.getElementById('cashPayDialog')?.addEventListener('click', e=>{ const r=e.target.getBoundingClientRect(); if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) e.target.close(); });
                </script>

                {{-- Success modal: Cetak vs Transaksi Baru --}}
                <dialog id="posSuccessDialog" style="border:0; padding:16px; background:transparent; max-width:420px; width:96%; margin:auto">
                    <div style="background:#fff; border-radius:20px; padding:24px; box-shadow:0 24px 60px rgba(0,0,0,0.35); border:1px solid #e5e7eb; text-align:center" x-data="{ invoice:'', receiptUrl:'' }" @pos-checkout-success.window="invoice=$event.detail.invoice; receiptUrl=$event.detail.receiptUrl; $el.closest('dialog').showModal()" @click.self="$el.closest('dialog').close()">
                        <div style="width:56px; height:56px; border-radius:9999px; background:#f0fdf4; border:1px solid #bbf7d0; display:grid; place-items:center; margin:0 auto; color:#15803d; font-size:24px">✓</div>
                        <h3 style="margin-top:12px; font-size:16px; font-weight:800; color:#0f172a">Transaksi Berhasil!</h3>
                        <p class="font-mono text-xs font-bold text-gray-600" style="margin-top:4px" x-text="invoice"></p>
                        <p style="margin-top:8px; font-size:12px; color:#64748b">Ingin cetak bukti transaksi?</p>
                        <div style="margin-top:16px; display:grid; grid-template-columns:1fr 1fr; gap:10px">
                            <button type="button" @click="if(receiptUrl) window.open(receiptUrl, '_blank'); $wire.resetPos(); $el.closest('dialog').close()" style="display:grid; place-items:center; border-radius:12px; background:#0f172a; color:#fff; padding:11px; font-size:13px; font-weight:700; cursor:pointer; width:100%">🖨️ Cetak Struk</button>
                            <button type="button" @click="$wire.resetPos(); $el.closest('dialog').close()" style="border-radius:12px; border:1px solid #e5e7eb; background:#f59e0b; color:#111827; padding:11px; font-size:13px; font-weight:800; cursor:pointer">Transaksi Baru</button>
                        </div>
                        <button type="button" @click="$wire.resetPos(); $el.closest('dialog').close()" style="margin-top:10px; font-size:11px; color:#94a3b8; background:transparent; border:0; cursor:pointer">Tutup</button>
                    </div>
                </dialog>
                <script>
                    document.addEventListener('livewire:init', () => {
                        Livewire.on('pos-checkout-success', (e) => {
                            document.getElementById('cashPayDialog')?.close();
                            const d = document.getElementById('posSuccessDialog');
                            if (d) {
                                // Alpine will pick via @pos-checkout-success.window, but also fallback
                                d.showModal();
                            }
                        });
                    });
                    document.getElementById('posSuccessDialog')?.addEventListener('click', e=>{
                        const r=e.target.getBoundingClientRect();
                        if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) e.target.close();
                    });
                </script>
                <p style="margin-top:0.7rem; text-align:center; font-size:11px; line-height:1.5; color:#6b7280;">Stok dipotong otomatis per resep. Jika bahan kurang, transaksi dibatalkan.</p>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
