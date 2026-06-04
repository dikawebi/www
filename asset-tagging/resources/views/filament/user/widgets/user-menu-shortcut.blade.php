<div style="font-family: sans-serif; max-width: 650px; margin: 5px 0; padding: 0 5px;">

    <div style="margin-bottom: 12px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0; padding: 0;">
            Halo, <span style="color: #2563eb;">{{ auth()->user()->name }}</span> 👋
        </h3>
        <p style="font-size: 11px; color: #6b7280; margin: 2px 0 0 0; padding: 0;">
            Silakan pilih menu ringkas di bawah untuk mulai bekerja:
        </p>
    </div>

    <div style="display: flex; gap: 10px; flex-wrap: wrap;">

        <a href="/user/scan-asset" style="display: inline-flex; align-items: center; gap: 8px; background-color: #2563eb; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.05); height: 38px; box-sizing: border-box;">
            <svg style="width: 16px; height: 16px; min-width: 16px; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
            </svg>
            <span>Scan Kamera QR</span>
        </a>

        <a href="/user/user-views" style="display: inline-flex; align-items: center; gap: 8px; background-color: white; color: #1f2937; border: 1px solid #e5e7eb; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.05); height: 38px; box-sizing: border-box;">
            <svg style="width: 16px; height: 16px; min-width: 16px; color: #4b5563; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75c.621 0 1.125.504 1.125 1.125v1.125c0 .621-.504 1.125-1.125 1.125H5.625A1.125 1.125 0 0 1 4.5 6.75V5.625c0-.621.504-1.125 1.125-1.125Z" />
            </svg>
            <span>Daftar Semua Aset</span>
        </a>

        <a href="/user/user-views/create" style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.05); height: 38px; box-sizing: border-box;">
            <svg style="width: 16px; height: 16px; min-width: 16px; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Daftar Aset Baru</span>
        </a>

    </div>
</div>
