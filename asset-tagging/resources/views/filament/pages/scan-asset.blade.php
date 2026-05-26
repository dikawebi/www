<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 max-w-xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 dark:bg-gray-950 dark:border-gray-800 text-center">

            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Arahkan QR Code ke Kamera</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pastikan pencahayaan cukup agar kode terbaca dengan jelas.</p>
            </div>

            <div class="overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 mx-auto"
                 id="reader"
                 style="width: 100%; max-width: 450px; min-height: 300px;">
            </div>

            <div class="mt-4 flex justify-center gap-4">
                <button onclick="startScanner()" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-500 shadow">
                    Nyalakan Kamera
                </button>
                <button onclick="stopScanner()" class="px-4 py-2 bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-300">
                    Matikan
                </button>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        let html5QrcodeScanner;

        function startScanner() {
            // Hindari duplikasi instance kamera
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            html5QrcodeScanner = new Html5Qrcode("reader");

            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                // Matikan kamera terlebih dahulu setelah QR berhasil terbaca (opsional)
                html5QrcodeScanner.stop();

                // Kirim data hasil scan langsung ke fungsi 'checkAsset' di Livewire PHP
                @this.call('checkAsset', decodedText);
            };

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            };

            // Jalankan kamera belakang secara default (facingMode: environment)
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                config,
                qrCodeSuccessCallback
            ).catch(err => {
                alert("Gagal mengakses kamera: " + err);
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                }).catch(err => console.log(err));
            }
        }

        // Jalankan scanner otomatis saat halaman selesai dimuat sepenuhnya
        document.addEventListener("DOMContentLoaded", function() {
            startScanner();
        });
    </script>
</x-filament-panels::page>
