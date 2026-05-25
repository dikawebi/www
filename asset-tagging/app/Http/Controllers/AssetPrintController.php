<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetPrintController extends Controller
{
    public function print($id)
    {
        // Pastikan relasi category ikut dimuat (eager loading)
        $asset = Asset::with('category')->findOrFail($id);

        // Menggabungkan beberapa data menjadi 1 teks string dengan enter (\n)
        $scanResultText = "ID Aset   : " . $asset->asset_id . "\n" .
                          "Nama      : " . $asset->name . "\n" .
                          "Kategori  : " . $asset->category->name;

        // Masukkan teks gabungan tadi ke dalam generator QR Code
        $qrcode = QrCode::size(120)->generate($scanResultText);

        return view('print-qr', compact('asset', 'qrcode'));
    }
}
