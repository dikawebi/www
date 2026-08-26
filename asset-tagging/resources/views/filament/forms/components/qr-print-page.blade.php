<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak QR Aset</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        /* Ukuran kartu: 4cm x 4cm fisik */
        :root {
            --card-size: 4cm;
            --qr-size: 3.1cm;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 20px;
            background: #f9fafb;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, var(--card-size));
            gap: 0.4cm;
            justify-content: start;
        }

        .qr-item {
            background: white;
            box-sizing: border-box;
            width: var(--card-size);
            height: var(--card-size);
            padding: 0.15cm;
            border-radius: 0.1cm;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .qr-item svg {
            width: var(--qr-size);
            height: var(--qr-size);
            flex-shrink: 0;
        }

        .asset-id {
            font-weight: 600;
            color: #111827;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0.08cm 0 0 0;
            letter-spacing: 0.02em;
        }

        /* Kertas A4: muat 4 kolom x ~6 baris per halaman */
        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm;
            }

            body { background: white; margin: 0; }
            .qr-item {
                box-shadow: none;
                border: 1px dashed #9ca3af;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="grid-container">
        @foreach($assets as $asset)
            <div class="qr-item">
                {!! QrCode::format('svg')->size(300)->margin(0)->generate($asset->asset_id) !!}
                <div class="asset-id">{{ $asset->asset_id }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
