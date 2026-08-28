@php
    use App\Support\OutletContext;
    $user = OutletContext::user();
    $currentOutletId = OutletContext::currentOutletId();
    $currentOutlet = OutletContext::currentOutlet();
    $outletOptions = OutletContext::selectableOutletOptions();
    $displayName = $currentOutlet?->name ?? 'Semua outlet';
@endphp

@if ($user)
    <div style="display:flex; align-items:center; flex-shrink:0; margin-right:6px">
        {{-- Pill trigger — high contrast on dark topbar --}}
        <button type="button" onclick="document.getElementById('outletPickerDialog')?.showModal()" title="{{ $displayName }}"
            style="display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:9999px; border:1px solid rgba(255,255,255,0.22); background:rgba(255,255,255,0.12); color:#fff; font-size:12.5px; font-weight:700; line-height:1; cursor:pointer; max-width:190px; backdrop-filter:blur(8px)">
            <span style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:9999px; background:#f59e0b; color:#111827; font-size:10px; font-weight:900; flex-shrink:0">{{ mb_strtoupper(mb_substr($displayName,0,1)) }}</span>
            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px" title="{{ $displayName }}">{{ $displayName }}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.9; flex-shrink:0"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    </div>

    {{-- Native <dialog> — always centered by browser, no teleport needed --}}
    <dialog id="outletPickerDialog" style="border:0; padding:16px; background:transparent; max-width:420px; width:94%; margin:auto">
        <div style="background:#ffffff; border-radius:20px; padding:22px; box-shadow:0 24px 60px rgba(0,0,0,0.35); border:1px solid #e5e7eb; color:#0f172a">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0">Pilih Outlet</h3>
                <button type="button" onclick="this.closest('dialog').close()" style="width:30px; height:30px; border-radius:9999px; border:1px solid #e5e7eb; background:#f8fafc; cursor:pointer; font-size:14px; line-height:1; color:#334155">✕</button>
            </div>
            <p style="margin:8px 0 0; font-size:12.5px; line-height:1.5; color:#334155; font-weight:500">Outlet aktif mempengaruhi data di <b>POS</b>, <b>stok</b> &amp; <b>laporan</b>.</p>

            @if ($user->isAdmin())
                <div style="margin-top:14px">
                    <input id="outletSearch" oninput="document.querySelectorAll('#outletPickerDialog [data-outlet]').forEach(el=> el.style.display = el.dataset.outlet.toLowerCase().includes(this.value.toLowerCase()) ? 'flex' : 'none')" placeholder="Cari outlet..." autofocus x-init="$el.focus()" style="width:100%; border-radius:12px; border:1.5px solid #cbd5e1; background:#fff; padding:10px 12px; font-size:13px; outline:none; color:#0f172a">
                </div>
                <form method="POST" action="{{ route('dashboard.outlet-context.update') }}" style="margin-top:12px; max-height:44vh; overflow:auto; padding-right:2px; display:flex; flex-direction:column; gap:7px">
                    @csrf
                    <button type="submit" name="selected_outlet_id" value="" data-outlet="semua outlet"
                        style="display:flex; width:100%; align-items:center; justify-content:space-between; border-radius:12px; border:1.5px solid {{ $currentOutletId===null ? '#f59e0b' : '#cbd5e1' }}; background:{{ $currentOutletId===null ? '#fffbeb' : '#fff' }}; padding:11px 14px; text-align:left; font-size:13px; font-weight:600; cursor:pointer; color:#0f172a">
                        <span>Semua outlet</span>
                        @if($currentOutletId===null)<span style="color:#b45309; font-weight:800">✓</span>@endif
                    </button>
                    @foreach ($outletOptions as $id => $name)
                        <button type="submit" name="selected_outlet_id" value="{{ $id }}" data-outlet="{{ mb_strtolower($name) }}"
                            style="display:flex; width:100%; align-items:center; justify-content:space-between; border-radius:12px; border:1.5px solid {{ (int)$currentOutletId===(int)$id ? '#f59e0b' : '#cbd5e1' }}; background:{{ (int)$currentOutletId===(int)$id ? '#fffbeb' : '#fff' }}; padding:11px 14px; text-align:left; font-size:13px; font-weight:500; cursor:pointer; color:#0f172a">
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-right:8px">{{ $name }}</span>
                            @if((int)$currentOutletId===(int)$id)<span style="flex-shrink:0; color:#b45309; font-weight:800">✓</span>@endif
                        </button>
                    @endforeach
                </form>
            @else
                <div style="margin-top:16px; border-radius:12px; border:1.5px solid #fcd34d; background:#fffbeb; padding:14px; font-size:13px; color:#78350f">
                    Outlet kamu: <b style="color:#92400e">{{ $currentOutlet?->name ?? '-' }}</b><br>
                    <span style="font-size:11px; color:#92400e">Hubungi Administrator untuk pindah outlet.</span>
                </div>
                <div style="margin-top:16px; display:flex; justify-content:flex-end">
                    <button type="button" onclick="this.closest('dialog').close()" style="border-radius:12px; background:#0f172a; color:#fff; padding:9px 18px; font-size:13px; font-weight:700; border:0; cursor:pointer">Tutup</button>
                </div>
            @endif
        </div>
    </dialog>
    <style>
        dialog::backdrop{ background:rgba(2,6,23,0.62); backdrop-filter:blur(3px) }
        dialog[open]{ display:grid; place-items:center; position:fixed; inset:0; width:100%; height:100%; max-width:100%; max-height:100%; z-index:9999 }
    </style>
    <script>document.getElementById('outletPickerDialog')?.addEventListener('click', e=>{ const r=e.target.getBoundingClientRect(); if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) e.target.close(); })</script>
@endif
