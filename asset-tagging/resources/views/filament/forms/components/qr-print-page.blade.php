<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak QR Aset</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            margin: 20px;
            background: #f9fafb;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        .qr-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .qr-item svg {
            margin-bottom: 10px;
            width: 100%;
            height: auto;
        }

        .asset-id {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
            margin: 5px 0;
        }

        .asset-name {
            color: #6b7280;
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media print {
            body { background: white; margin: 0; }
            .qr-item {
                box-shadow: none;
                border: 1px solid #d1d5db;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="grid-container">
        @foreach($assets as $asset)
            <div class="qr-item">
                {!! QrCode::format('svg')->size(140)->generate($asset->asset_id) !!}
                <div class="asset-id">{{ $asset->asset_id }}</div>
                <div class="asset-name">{{ $asset->name }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
