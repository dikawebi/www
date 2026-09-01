<x-filament-panels::page>
    <style>
        .pos-hist{display:flex;flex-direction:column;gap:20px}
        .pos-hist-card{border:1px solid #e5e7eb;border-radius:1.15rem;background:#fff;overflow:hidden}
        .dark .pos-hist-card{border-color:#334155;background:#1f2937}
        .pos-hist-row{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f1f5f6;transition:background 120ms}
        .dark .pos-hist-row{border-bottom-color:#1e293b}
        .pos-hist-row:last-child{border-bottom:none}
        .pos-hist-row:hover{background:#f9fafb}
        .dark .pos-hist-row:hover{background:#0f172a}
        .pos-hist-label{font-size:13px;color:#6b7280;font-weight:500}
        .pos-hist-val{font-size:13px;font-weight:700;color:#111827}
        .dark .pos-hist-val{color:#f1f5f9}
        .pos-badge{display:inline-block;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:700}
        .pos-badge-success{background:#d1fae5;color:#065f46}
        .dark .pos-badge-success{background:#064e3b;color:#6ee7b7}
        .pos-badge-cash{background:#fef3c7;color:#92400e}
        .dark .pos-badge-cash{background:#452e00;color:#fbbf24}
        .pos-inv{font-family:ui-monospace,monospace;font-size:11px;font-weight:700;background:#f3f4f6;padding:2px 6px;border-radius:4px;color:#374151}
        .dark .pos-inv{background:#1e293b;color:#d1d5db}
    </style>

    <div class="pos-hist">
        {{-- Filter --}}
        <div class="pos-hist-card" style="padding:16px">
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;margin-bottom:4px">Dari</label>
                    <input type="date" wire:model.live.debounce.300ms="startDate" style="border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;background:#fff;outline:none" class="dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;margin-bottom:4px">Sampai</label>
                    <input type="date" wire:model.live.debounce.300ms="endDate" style="border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;background:#fff;outline:none" class="dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                </div>
                @if($this->isAdminUser())
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;margin-bottom:4px">Outlet</label>
                        <select wire:model.live.debounce.300ms="outletId" style="border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;background:#fff;outline:none;min-width:160px" class="dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                            <option value="">Semua outlet</option>
                            @foreach($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="button" wire:click="form.fill({{ json_encode(['startDate' => $startDate, 'endDate' => $endDate, 'outletId' => $outletId]) }})"
                    style="border:1px solid #e5e7eb;background:#fff;border-radius:10px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer">↻ Muat Ulang</button>
            </div>
        </div>

        {{-- Transaction list --}}
        <div class="pos-hist-card">
            @php $txs = $this->getTransactions(); @endphp
            @forelse($txs as $tx)
                <div class="pos-hist-row">
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="pos-inv">{{ $tx->invoice_number }}</span>
                            <span class="pos-badge pos-badge-success">Selesai</span>
                            @if(!empty($tx->payments) && count($tx->payments) > 1)
                                <span class="pos-badge pos-badge-cash">Split</span>
                            @endif
                        </div>
                        <div style="display:flex;gap:12px;margin-top:4px;flex-wrap:wrap">
                            <span class="pos-hist-label">{{ $tx->transaction_date->format('d M Y H:i') }}</span>
                            <span class="pos-hist-label">{{ $tx->outlet?->name ?? '-' }}</span>
                            <span class="pos-hist-label">{{ $tx->cashier?->name ?? '-' }}</span>
                        </div>
                        <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap">
                            @foreach($tx->items->take(5) as $item)
                                <span style="background:#f1f5f9;border-radius:6px;padding:2px 6px;font-size:10px;color:#475569;white-space:nowrap">{{ $item->menuItem?->name }} ×{{ $item->quantity }}</span>
                            @endforeach
                            @if($tx->items->count() > 5)
                                <span style="background:#f1f5f9;border-radius:6px;padding:2px 6px;font-size:10px;color:#94a3b8">+{{ $tx->items->count() - 5 }} lagi</span>
                            @endif
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:14px;font-weight:800;color:#111827" class="dark:text-white">{{ $this->formatRupiah($tx->total_amount) }}</div>
                        @if($tx->change_amount > 0)
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px">Kembali: {{ $this->formatRupiah($tx->change_amount) }}</div>
                        @endif
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px">{{ ucfirst($tx->payment_method) }}</div>
                    </div>
                </div>
            @empty
                <div style="padding:48px 20px;text-align:center">
                    <div style="font-size:28px;margin-bottom:8px">📋</div>
                    <div style="font-size:14px;font-weight:700;color:#374151" class="dark:text-white">Belum ada transaksi</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px">Transaksi dari POS akan muncul di sini.</div>
                </div>
            @endforelse
            @if($txs->isNotEmpty())
                <div style="padding:12px 16px;border-top:1px solid #e5e7eb;background:#f9fafb;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px" class="dark:border-gray-700 dark:bg-gray-900">
                    <span style="font-size:12px;color:#6b7280;font-weight:600">{{ $txs->count() }} transaksi</span>
                    <span style="font-size:13px;font-weight:800;color:#111827" class="dark:text-white">Total: {{ $this->formatRupiah($txs->sum('total_amount')) }}</span>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
