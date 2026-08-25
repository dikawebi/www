<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; color: #111; background: #fff; }
        .receipt { width: 80mm; max-width: 100%; margin: 0 auto; padding: 6mm 4mm; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { color: #6b7280; font-size: 10px; }
        .divider { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .item-name { flex: 1; }
        .total-row { font-weight: 700; font-size: 12px; margin-top: 4px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
        @media screen {
            body { background: #f3f4f6; padding: 12px; }
            .receipt { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.15); }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center">
        <div class="bold" style="font-size:13px;">{{ $transaction->outlet->name ?? config('app.name') }}</div>
        @if ($transaction->outlet?->address)<div class="muted">{{ $transaction->outlet->address }}</div>@endif
        @if ($transaction->outlet?->phone)<div class="muted">Tel: {{ $transaction->outlet->phone }}</div>@endif
    </div>
    <hr class="divider">
    <div class="row muted"><span>Invoice</span><span class="bold" style="color:#111;">{{ $transaction->invoice_number }}</span></div>
    <div class="row muted"><span>Tanggal</span><span>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span></div>
    <div class="row muted"><span>Kasir</span><span>{{ $transaction->cashier?->name ?? '-' }}</span></div>
    <div class="row muted"><span>Bayar</span><span>{{ ucfirst($transaction->payment_method) }}</span></div>
    <div class="row muted"><span>Status</span><span>{{ $transaction->status }}</span></div>
    <hr class="divider">
    @foreach ($transaction->items as $item)
        <div class="row"><span class="item-name">{{ $item->menuItem->name }}</span><span>{{ number_format($item->quantity, 0) }}× {{ number_format($item->price, 0, ',', '.') }}</span></div>
        <div class="row muted"><span></span><span>{{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
    @endforeach
    <hr class="divider">
    <div class="row total-row"><span>Total</span><span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span></div>
    <hr class="divider">
    <div class="center muted">Terima kasih — Sampai jumpa lagi</div>
    <div class="center muted" style="margin-top:4px;">{{ $transaction->created_at->format('d M Y H:i') }}</div>
    <div class="center no-print" style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
        <button onclick="window.print()" style="padding:6px 14px; border:1px solid #111; background:#111; color:#fff; border-radius:6px; cursor:pointer;">Cetak / Simpan PDF</button>
        <a href="{{ url('/dashboard') }}" style="padding:6px 14px; border:1px solid #ccc; background:#fff; color:#111; border-radius:6px; text-decoration:none;">Kembali</a>
    </div>
</div>
<script>window.addEventListener('load', () => { /* auto print optional: uncomment below */ /* setTimeout(()=>window.print(), 400); */ });</script>
</body>
</html>
