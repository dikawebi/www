<div class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
    @php
        // Mengambil record data saat ini (untuk halaman Edit/View)
        // atau mengambil live state name dari form (untuk halaman Create)
        $record = $getRecord();
        $assetId = $record ? $record->asset_id : $get('asset_id');
        $assetName = $record ? $record->name : $get('name');
    @endphp

    @if($assetId)
        <div class="flex flex-col items-center gap-2">
            <div class="p-2 bg-white rounded-lg border border-gray-100 shadow-inner">
                {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->margin(1)->generate($assetId) !!}
            </div>

            <div class="text-center mt-1">
                <p class="text-sm font-bold tracking-wider text-gray-900 dark:text-white uppercase">
                    {{ $assetId }}
                </p>

                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 max-w-[180px] truncate mt-0.5" title="{{ $assetName ?? 'Nama Aset Belum Diisi' }}">
                    {{ $assetName ?? 'Nama belum diisi...' }}
                </p>
            </div>
        </div>
    @else
        <div class="text-center py-6">
            <x-heroicon-o-qr-code class="mx-auto h-12 w-12 text-gray-400 animate-pulse" />
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                Menunggu kategori & data aset...
            </p>
        </div>
    @endif
</div>
