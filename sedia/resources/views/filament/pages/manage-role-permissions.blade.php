<x-filament-panels::page>
    <style>
        .mp-wrap *{box-sizing:border-box}
        .mp-tabs{display:inline-flex;background:#f3f4f6;padding:4px;border-radius:9999px;gap:4px}
        .dark .mp-tabs{background:#1f2937}
        .mp-tab{padding:8px 18px;border-radius:9999px;font-weight:700;font-size:13px;border:0;cursor:pointer;background:transparent;color:#6b7280}
        .mp-tab.active{background:#111827;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.15)}
        .dark .mp-tab.active{background:#f59e0b;color:#111827}
        .mp-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;overflow:hidden}
        .dark .mp-card{border-color:#334155;background:#1e2937}
        .mp-table{width:100%;border-collapse:collapse;table-layout:fixed}
        .mp-table th{background:#f8fafc;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:12px 12px;text-align:center;white-space:nowrap;border-bottom:1px solid #e5e7eb}
        .dark .mp-table th{background:#111827;color:#94a3b8;border-color:#334155}
        .mp-table th:first-child{text-align:left;padding-left:20px;width:40%}
        .mp-table th.perm{width:15%}
        .mp-table td{padding:14px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:center}
        .dark .mp-table td{border-color:#2d3748}
        .mp-table tr:last-child td{border-bottom:0}
        .mp-table tr:hover td{background:#f8fafc}
        .dark .mp-table tr:hover td{background:#1a2332}
        .mp-table td:first-child{text-align:left;padding-left:20px}
        .mp-name{font-size:13.5px;font-weight:600;color:#0f172a;line-height:1.2}
        .dark .mp-name{color:#f1f5f9}
        .mp-key{margin-top:3px;display:inline-block;background:#f1f5f9;color:#64748b;font-family:ui-monospace,monospace;font-style:italic;font-size:10px;padding:2px 6px;border-radius:6px;letter-spacing:.02em}
        .dark .mp-key{background:#0f172a;color:#94a3b8}
        .mp-badge{display:inline-block;margin-left:6px;background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:9999px;vertical-align:middle}
        .dark .mp-badge{background:#452e00;color:#fbbf24}
        .mp-check{width:22px;height:22px;accent-color:#16a34a;cursor:pointer}
        .mp-check:checked{accent-color:#16a34a;background:#16a34a}
        .mp-check-wrap{display:inline-flex;align-items:center;justify-content:center;min-width:44px;min-height:44px;padding:11px;cursor:pointer}
        .mp-table td:has(.mp-check){padding:11px 6px}
        .mp-dash{color:#cbd5e1;font-size:16px}
        .dark .mp-dash{color:#475569}
        .mp-action-btn{border:1px solid #e5e7eb;background:#fff;border-radius:10px;padding:9px 14px;font-size:12px;font-weight:700;cursor:pointer;min-height:44px}
        .dark .mp-action-btn{border-color:#334155;background:#0f172a;color:#e2e8f0}
        @media(max-width:768px){
            .mp-table th:first-child{width:48%}
            .mp-table th.perm{width:13%}
            .mp-table td,.mp-table th{padding:11px 6px}
            .mp-table th{font-size:9px}
            .mp-name{font-size:12px}
        }
    </style>

    {{-- Header: Role + Actions --}}
    <div style="display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:flex-start">
        <div>
            <div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8">Pilih peran yang diatur</div>
            <div class="mp-tabs" style="margin-top:8px">
                <button type="button" wire:click="$set('selectedRole','staff')" class="mp-tab {{ $selectedRole==='staff' ? 'active' : '' }}">Staff Outlet</button>
                <button type="button" wire:click="$set('selectedRole','admin')" class="mp-tab {{ $selectedRole==='admin' ? 'active' : '' }}">Administrator</button>
            </div>
            <div style="margin-top:8px;font-size:12px;color:#64748b;line-height:1.5">
                @if($selectedRole==='staff')
                    Atur menu untuk <b style="color:#1e293b">Staff Outlet</b>. Halaman <b>POS &amp; Laporan</b> hanya butuh <b>Lihat</b>.
                @else
                    <b style="color:#1e293b">Administrator</b> default akses penuh — matikan bila perlu dibatasi.
                @endif
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <button type="button" wire:click="toggleAll(true)" class="mp-action-btn">✓ Centang semua</button>
            <button type="button" wire:click="toggleAll(false)" class="mp-action-btn">✕ Kosongkan</button>
            <x-filament::button wire:click="save" icon="heroicon-o-check" color="primary" style="border-radius:10px">Simpan Perubahan</x-filament::button>
            <x-filament::button wire:click="resetToDefault" icon="heroicon-o-arrow-path" color="gray" requiresConfirmation style="border-radius:10px">Reset Default</x-filament::button>
        </div>
    </div>

    {{-- Helper --}}
    <div style="margin-top:16px;display:flex;gap:10px;align-items:flex-start;background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.6;color:#1e40af">
        <span style="font-size:14px;line-height:1">💡</span>
        <span><b>Cara baca:</b> 1 baris = 1 menu. Centang <b>Lihat</b> agar menu muncul di sidebar. <b>Tambah / Ubah / Hapus</b> mengatur tombol aksi di dalam halaman. Klik <b>Simpan Perubahan</b> agar aktif.</span>
    </div>

    {{-- Flat table — 4 hak sebaris --}}
    <div class="mp-card" style="margin-top:18px;overflow-x:auto">
        <table class="mp-table">
            <thead>
                <tr>
                    <th>Nama Fitur</th>
                    <th class="perm">Lihat</th>
                    <th class="perm">Tambah</th>
                    <th class="perm">Ubah</th>
                    <th class="perm">Hapus</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Support\RolePermission::resourceMap() as $key => $label)
                    @php $isPage = in_array($key, ['Pos','TutupKasirHarian','SaranReorder']) || str_contains($key,'Report'); @endphp
                    <tr>
                        <td>
                            <div class="mp-name">{{ $label }}</div>
                            <span class="mp-key">{{ $key }}</span>
                            @if($isPage)
                                <span class="mp-badge">hanya Lihat</span>
                            @endif
                        </td>
                        <td><label class="mp-check-wrap"><input type="checkbox" wire:model.live="permissions.{{ $key }}.view" class="mp-check" title="Lihat {{ $label }}"></label></td>
                        <td>
                            @if($isPage)
                                <span class="mp-dash">—</span>
                            @else
                                <label class="mp-check-wrap"><input type="checkbox" wire:model.live="permissions.{{ $key }}.create" class="mp-check" title="Tambah {{ $label }}"></label>
                            @endif
                        </td>
                        <td>
                            @if($isPage)
                                <span class="mp-dash">—</span>
                            @else
                                <label class="mp-check-wrap"><input type="checkbox" wire:model.live="permissions.{{ $key }}.edit" class="mp-check" title="Ubah {{ $label }}"></label>
                            @endif
                        </td>
                        <td>
                            @if($isPage)
                                <span class="mp-dash">—</span>
                            @else
                                <label class="mp-check-wrap"><input type="checkbox" wire:model.live="permissions.{{ $key }}.delete" class="mp-check" title="Hapus {{ $label }}"></label>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 18px;background:#f8fafc;border-top:1px solid #e5e7eb;flex-wrap:wrap">
            <span style="font-size:12px;color:#64748b">Tips: pakai <b>Centang semua</b> lalu matikan yang tidak perlu — lebih cepat.</span>
            <x-filament::button wire:click="save" icon="heroicon-o-check" color="primary">Simpan Perubahan</x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
