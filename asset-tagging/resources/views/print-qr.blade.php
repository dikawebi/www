<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Code - {{ $asset->asset_id }}</title>
    <style>
        /* Pengaturan standar cetak label fisik */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ffffff;
        }
        .ticket-card {
            text-align: center;
            padding: 15px;
            border: 2px dashed #000000; /* Batas potong label */
            border-radius: 8px;
            width: 200px; /* Lebar standar label tag */
            background: #ffffff;
        }
        .qr-image {
            margin-bottom: 10px;
        }
        .asset-id {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0 2px 0;
            letter-spacing: 1px;
        }
        .asset-name {
            font-size: 11px;
            color: #444444;
            margin: 0;
            font-weight: 500;
            /* Menjaga nama panjang agar tidak merusak layout cetak */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        /* Menyembunyikan elemen tidak penting saat dicetak ke kertas */
        @media print {
            body { padding: 0; }
            .ticket-card { border: none; } /* Hilangkan border putus-putus saat print asli jika diinginkan */
        }
    </style>
</head>
<body>

    <div class="ticket-card">
        <div class="qr-image">
            {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($asset->asset_id) !!}
        </div>

        <div class="asset-id">
            {{ $asset->asset_id }}
        </div>

        <div class="asset-name">
            {{ $asset->name }}
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
