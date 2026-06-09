<div style="font-family: sans-serif; max-width: 650px; margin: 5px 0; padding: 0 5px;">
    <div style="margin-bottom: 12px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0; padding: 0;">
            Halo, <span style="color: #2563eb;">{{ auth()->user()->name }}</span> 👋
        </h3>
    </div>

    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button id="btn-pemicu-scan" style="display: inline-flex; align-items: center; gap: 8px; background-color: #2563eb; color: white; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; height: 38px; box-sizing: border-box; border: none; cursor: pointer;">
            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
            </svg>
            <span>Scan Kamera QR</span>
        </button>

        <a href="/user/assets" style="display: inline-flex; align-items: center; gap: 8px; background-color: white; color: #1f2937; border: 1px solid #e5e7eb; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; height: 38px;">
            <span>Daftar Semua Aset</span>
        </a>
    </div>

    <div id="wrapper-scanner-qr" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 999999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; padding: 20px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center;">
            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #111827; font-weight: 600;">Arahkan Kamera ke QR Code</h4>
            <div id="box-kamera-scanner" style="width: 100%; min-height: 250px; background: #f3f4f6; border-radius: 8px; overflow: hidden;"></div>
            <button id="btn-batal-scan" style="margin-top: 15px; background: #ef4444; color: white; border: none; padding: 8px 20px; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 600;">
                Batalkan Pemindaian
            </button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let html5QrcodeScanner = null;
    const pemicuBtn = document.getElementById('btn-pemicu-scan');
    const batalBtn = document.getElementById('btn-batal-scan');
    const wrapper = document.getElementById('wrapper-scanner-qr');

    if(pemicuBtn) {
        pemicuBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // 1. Amankan visual: Tampilkan box pop-up hitam terlebih dahulu ke layar!
            wrapper.style.display = 'flex';

            // 2. Inisialisasi engine kamera
            try {
                html5QrcodeScanner = new Html5Qrcode("box-kamera-scanner");
                html5QrcodeScanner.start(
                    { facingMode: { ideal: "environment" } }, // Fallback cerdas agar jalan di webcam laptop & hp
                    { fps: 10, qrbox: 230 },
                    (qrCodeText) => {
                        bersihkanScanner();
                        fetch(`/api/get-asset-id-by-code?code=${encodeURIComponent(qrCodeText)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.id) {
                                    window.location.href = `/user/assets/${data.id}`;
                                } else {
                                    alert(`Kode "${qrCodeText}" tidak terdaftar di database.`);
                                }
                            }).catch(() => alert("Eror koneksi server."));
                    },
                    (errorMessage) => {}
                ).catch(err => {
                    console.error("Kamera gagal start:", err);
                    // Tetap biarkan modal terbuka agar user tahu ada isu izin kamera
                });
            } catch (error) {
                console.error("Gagal instansiasi Html5Qrcode:", error);
            }
        });
    }

    if(batalBtn) {
        batalBtn.addEventListener('click', function() {
            bersihkanScanner();
        });
    }

    function bersihkanScanner() {
        wrapper.style.display = 'none';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
            }).catch(err => console.log(err));
        }
    }
});
</script>
