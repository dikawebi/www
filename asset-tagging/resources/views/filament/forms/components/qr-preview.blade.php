<div class="flex flex-col items-center justify-center p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-800">
    <div class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">
        Pratinjau QR Code (Live Scan)
    </div>

    @if($getRecord())
        @php
            // Gabungkan teks string yang sama persis dengan format print kemarin
            $scanResultText = "ID Aset   : " . $getRecord()->asset_id . "\n" .
                              "Nama      : " . $getRecord()->name . "\n" .
                              "Kategori  : " . $getRecord()->category->name;

            // Generate QR Code dalam bentuk SVG string
            $qrcode = SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)->generate($scanResultText);
        @endphp

        <div class="p-2 bg-white rounded-lg">
            {!! $qrcode !!}
        </div>

        <div class="mt-2 text-xs font-mono text-gray-400">
            {{ $getRecord()->asset_id }}
        </div>
    @else
        <div class="text-xs italic text-gray-400 py-6">
            QR Code otomatis tersedia setelah aset disimpan.
        </div>
    @endif
</div>
