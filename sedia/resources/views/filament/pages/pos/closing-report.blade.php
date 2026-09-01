<x-filament-panels::page>
    <style>
        .pos-close{display:flex;flex-direction:column;gap:20px}
        .pos-close-card{border:1px solid #e5e7eb;border-radius:1.15rem;background:#fff;overflow:hidden}
        .dark .pos-close-card{border-color:#334155;background:#1f2937}
        .pos-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;padding:16px}
        .pos-kpi-item{border:1px solid #e5e7eb;border-radius:1rem;padding:14px 16px;background:#f9fafb;text-align:center}
        .dark .pos-kpi-item{border-color:#374151;background:#111827}
        .pos-kpi-label{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#94a3b8}
        .pos-kpi-val{font-size:18px;font-weight:900;color:#111827;margin-top:4px}
        .dark .pos-kpi-val{color:#f1f5f9}
        .pos-close-row{display:grid;grid-template-columns:1fr auto auto;gap:12px;align-items:center;padding:14px 16px;border-bottom:1px solid #f1f5f6}
        .dark .pos-close-row{border-bottom-color:#1e293b}
        .pos-close-row:last-child{border-bottom:none}
        .pos-close-row:hover{background:#f9fafb}
        .dark .pos-close-row:hover{background:#0f172a}
        .pos-total-bar{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-top:1px solid #e5e7eb;background:#f9fafb;border-radius:0 0 1.15rem 1.15rem}
        .dark .pos-total-bar{border-color:#374151;background:#111827}
    </style>

    <div class="pos-close">
        {{-- Filter --}}
        <div class="pos-close-card" style="padding:16px">
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;margin-bottom:4px">Tanggal</label>
                    <input type="date" wire:model.live.debounce.300ms="selectedDate" style="border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;background:#fff;outline:none" class="dark:bg-gray-800 dark:border-gray-600 dark:text-white">
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
                <span style="font-size:12px;color:#6b7280;font-weight:600">{{ $this->currentOutletName() }}</span>
            </div>
        </div>

        @php
            $rows = $this->getRows();
            $grand = $this->getGrandTotal($rows);
        @endphp

        {{-- KPI --}}
        <div class="pos-close-card">
            <div class="pos-kpi">
                <div class="pos-kpi-item">
                    <div class="pos-kpi-label">Total Transaksi</div>
                    <div class="pos-kpi-val">{{ $grand['trx_count'] }}</div>
                </div>
                <div class="pos-kpi-item">
                    <div class="pos-kpi-label">Total Omzet</div>
                    <div class="pos-kpi-val">{{ $this->formatRupiah($grand['total']) }}</div>
                </div>
                @foreach($grand['by_payment'] as $method => $amount)
                    <div class="pos-kpi-item">
                        <div class="pos-kpi-label">{{ ucfirst($method) }}</div>
                        <div class="pos-kpi-val" style="font-size:15px">{{ $this->formatRupiah($amount) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Detail per kasir --}}
        <div class="pos-close-card">
            <div style="padding:14px 16px;border-bottom:1px solid #f1f5f6;display:flex;justify-content:space-between;align-items:center">
                <h3 style="font-size:14px;font-weight:800;color:#111827" class="dark:text-white">Detail per Kasir</h3>
                <span style="font-size:12px;color:#94a3b8">{{ $rows->count() }} kasir aktif</span>
            </div>
            @forelse($rows as $row)
                <div class="pos-close-row">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#111827" class="dark:text-white">{{ $row['cashier_name'] }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px">{{ $row['trx_count'] }} transaksi</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:13px;font-weight:800;color:#111827" class="dark:text-white">{{ $this->formatRupiah($row['total']) }}</div>
                    </div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        @foreach($row['by_payment'] as $method => $amount)
                            <span style="background:#f1f5f9;border-radius:6px;padding:2px 6px;font-size:10px;color:#475569" class="dark:bg-gray-800 dark:text-gray-300">{{ ucfirst($method) }}: {{ $this->formatRupiah($amount) }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div style="padding:48px 20px;text-align:center">
                    <div style="font-size:28px;margin-bottom:8px">📊</div>
                    <div style="font-size:14px;font-weight:700;color:#374151" class="dark:text-white">Belum ada data</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px">Pilih tanggal dan outlet untuk melihat rekap kasir.</div>
                </div>
            @endforelse

            @if($rows->isNotEmpty())
                <div class="pos-total-bar">
                    <span style="font-size:12px;font-weight:700;color:#6b7280">GRAND TOTAL</span>
                    <span style="font-size:15px;font-weight:900;color:#111827" class="dark:text-white">{{ $this->formatRupiah($grand['total']) }}</span>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
