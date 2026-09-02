<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <style>
        @page { size: 58mm auto; margin: 3mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; color: #111; background: #fff; line-height: 1.4; }
        .receipt { width: 56mm; max-width: 100%; margin: 0 auto; padding: 5mm 3mm; font-size: 11px; line-height: 1.4; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .brand { font-size: 15px; font-weight: 800; letter-spacing: 0.02em; line-height: 1.1; }
        .muted { color: #222; font-size: 9.5px; }
        .tiny { font-size: 9px; color: #444; }
        .divider { border: none; border-top: 1px dashed #000; margin: 7px 0; }
        .divider-bold { border-top: 1px solid #000; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .item-line { display: flex; gap: 6px; align-items: flex-start; }
        .qty { width: 18px; flex-shrink: 0; text-align: right; }
        .name { flex: 1; word-break: break-word; }
        .price { width: 62px; flex-shrink: 0; text-align: right; white-space: nowrap; }
        .total-row { font-weight: 800; font-size: 11px; margin-top: 3px; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
        @media screen { body { background: #e5e7eb; padding: 12px; } .receipt { background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12); } }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center">
        @php
            $logo = \App\Support\Branding::appLogoUrl();
            $appName = \App\Support\Branding::appName();
            $outletName = $transaction->outlet->name ?? '';
            // Nama usaha - Nama cabang
            $businessOutlet = $outletName ? ($appName.' - '.$outletName) : $appName;
            $branchAddress = $transaction->outlet?->address ?? \App\Support\Branding::businessAddress();
            $branchPhone = $transaction->outlet?->phone ?? \App\Support\Branding::businessPhone();
        @endphp
        @if($logo)<div style="margin-bottom:5px;"><img src="{{ $logo }}" alt="Logo" style="max-height:28px; max-width:120px; object-fit:contain; display:inline-block"></div>@endif
        <div class="brand">{{ $businessOutlet }}</div>
        @if($transaction->outlet?->receipt_header)
            <div class="tiny" style="white-space:pre-line; margin-top:3px">{{ $transaction->outlet->receipt_header }}</div>
        @else
            @if($branchAddress)<div class="tiny">{{ $branchAddress }}</div>@endif
            @if($branchPhone)<div class="tiny">Tel: {{ $branchPhone }}</div>@endif
        @endif
        <div class="tiny" style="margin-top:2px">www.sedia-pos.com</div>
    </div>
    <hr class="divider">
    <div class="row tiny"><span>Check No : {{ $transaction->invoice_number }}</span></div>
    <div class="row tiny"><span>{{ $transaction->transaction_date->format('d M y H:i:s') }} &nbsp; {{ $transaction->outlet->name ?? '' }} POS{{ $transaction->outlet_id }}</span></div>
    <div class="row tiny"><span>Kasir: {{ $transaction->cashier?->name ?? $transaction->outlet->name ?? '-' }}</span></div>
    <hr class="divider">
    {{-- Items — format baru: Nama di baris 1, qty x harga → total di baris 2 --}}
    @foreach($transaction->items as $item)
        <div style="margin-top:4px">
            <div style="font-weight:700">{{ $item->menuItem->name }}</div>
            <div class="row">
                <span>{{ $item->quantity }}x {{ number_format($item->price, 0, ',', '.') }}</span>
                <span>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</span>
            </div>
        </div>
    @endforeach
    <hr class="divider">
    <div class="row"><span>Subtotal :</span><span>{{ number_format($transaction->total_amount, 0, ',', '.') }}</span></div>
    <div class="row bold"><span>Total:</span><span>{{ number_format($transaction->total_amount, 0, ',', '.') }}</span></div>
    <div class="row"><span>Payment :</span><span>{{ ucfirst($transaction->payment_method) }} {{ number_format($transaction->paid_amount ?: $transaction->total_amount, 0, ',', '.') }}</span></div>
    @if($transaction->change_amount > 0)
        <div class="row"><span>Kembalian :</span><span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
    @endif
    @if(!empty($transaction->payments) && count($transaction->payments) > 1)
        @foreach($transaction->payments as $pay)
            <div class="row tiny"><span>{{ ucfirst($pay['method']) }}</span><span>{{ number_format($pay['amount'],0,',','.') }}</span></div>
        @endforeach
    @endif
    <div class="row tiny"><span>---{{ $transaction->invoice_number }} CLOSED {{ $transaction->updated_at->format('d M y H:i:s') }}---</span></div>
    <hr class="divider">
    <div class="center tiny" style="margin-top:6px; line-height:1.4">
        @if($transaction->outlet?->receipt_footer)
            <div style="white-space:pre-line">{{ $transaction->outlet->receipt_footer }}</div>
        @else
            Thank You<br>Please Come Again<br><span style="font-size:8px">Barang yang sudah dibeli tidak dapat ditukar</span>
        @endif
    </div>
    <div class="center no-print" style="margin-top:12px; display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
        <button onclick="window.print()" style="padding:7px 14px; border:1px solid #111; background:#111; color:#fff; border-radius:12px; cursor:pointer; font-weight:700">Cetak</button>
        <a href="{{ url('/pos') }}" style="padding:7px 14px; border:1px solid #d1d5db; background:#fff; color:#111; border-radius:12px; text-decoration:none; font-weight:600">Kembali ke POS</a>
        <a href="{{ url('/dashboard') }}" style="padding:7px 14px; border:1px solid #d1d5db; background:#fff; color:#111; border-radius:12px; text-decoration:none; font-weight:600">Dashboard</a>
    </div>
</div>
</body>
</html>
