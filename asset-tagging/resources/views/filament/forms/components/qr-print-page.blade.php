<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak QR Aset</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            margin: 5mm 10mm;
            background: #f9fafb;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3mm;
        }

        .qr-item {
            background: white;
            width: 4cm;
            height: 4cm;
            padding: 1mm;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .qr-item svg {
            width: 3cm;
            height: 3cm;
        }

        .asset-id {
            font-weight: 600;
            color: #111827;
            font-size: 8px;
            margin: 1mm 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        @media print {
            body { background: white; margin: 5mm 10mm; }
            .qr-item { border: 1px solid #d1d5db; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="grid-container">
        @foreach($assets as $asset)
            <div class="qr-item">
                {!! QrCode::format('svg')->size(140)->generate($asset->asset_id) !!}
                <div class="asset-id">{{ $asset->asset_id }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
