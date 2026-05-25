<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label - {{ $asset->asset_id }}</title>
    <style>
        /* Mengatur ukuran kertas cetak stiker (misal: 80mm x 50mm) */
        @page {
            size: 80mm 50mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 5mm;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50mm; /* Menyamakan dengan tinggi @page */
        }

        /* Pembungkus utama label */
        .label-box {
            border: 2px solid #000;
            border-radius: 4px;
            padding: 10px;
            width: 70mm; /* Membatasi lebar agar tidak melar semeter penuh */
            height: 42mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        /* Area QR Code */
        .qr-code {
            margin-bottom: 8px;
            display: inline-block;
        }

        .qr-code svg {
            width: 100px !important;
            height: 100px !important;
        }

        /* Teks ID Asset */
        .id-text {
            font-weight: bold;
            font-size: 16px;
            color: #000;
            letter-spacing: 1px;
            margin: 4px 0 2px 0;
        }

        /* Teks Detail Nama Barang & Kategori */
        .detail-text {
            font-size: 12px;
            color: #333;
            font-weight: 500;
            line-height: 1.3;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; /* Jika nama terlalu panjang akan dipotong dengan '...' */
        }
    </style>
</head>
<body>

    <div class="label-box">
        <div class="qr-code">
            {!! $qrcode !!}
        </div>

        <div class="id-text">
            {{ $asset->asset_id }}
        </div>

        <div class="detail-text">
            {{ $asset->name }} | {{ $asset->category->name }}
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
