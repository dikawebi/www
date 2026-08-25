# Sedia — Sistem Manajemen Outlet, Persediaan & Penjualan

<p align="center">
  <img src="public/manual-screenshots/01-dashboard.svg" width="800" alt="Sedia Dashboard Preview">
</p>

<p align="center">
  <a href="/manual"><b>📘 User Manual (Baca di Browser)</b></a> •
  <a href="/USER_MANUAL_SEDIA.pdf">⬇ Download PDF (726 KB)</a> •
  <a href="/USER_MANUAL_SEDIA.doc">⬇ Download Word (23 KB)</a>
</p>

**Sedia** adalah aplikasi Laravel + Filament untuk mengelola **banyak outlet** dalam satu tempat: POS kasir ringan yang potong stok otomatis, transfer stok antar outlet, stock opname, karyawan/kasbon/gaji, dan 6 laporan siap cetak.

- **Stack:** Laravel 13 · PHP 8.3 · Filament 5.7 · Tailwind 4 · Vite · SQLite/PostgreSQL
- **Deploy:** Vercel-ready (`vercel.json`, `api/index.php`)
- **PWA:** POS dapat di-instal di HP (Chrome → Instal aplikasi)

---

## ✨ Fitur Utama

| Grup | Fitur |
|------|-------|
| **Penjualan** | POS Kasir (grid kategori, keranjang, potong stok otomatis), Transaksi (void admin, struk 80mm), Tutup Kasir Harian (per kasir & metode bayar) |
| **Produk** | Bahan Baku (cost/unit, min stock), Menu + Resep (qty per unit) |
| **Persediaan** | Saldo Stok (read-only), Transfer Stok (Draft→Kirim→Terima/Batal, restore otomatis), Stock Opname (Terapkan → lock), Saran Reorder (30 hari, 7 hari target) |
| **Operasional** | Karyawan, Kasbon (Pending→Approve/Reject + notifikasi), Penggajian (draft/paid, hitung server) |
| **Laporan** | 6 laporan (Penjualan/Outlet, Menu Terlaris, Pemakaian Bahan, Selisih Opname, Gaji & Kasbon, Laba/Menu) — KPI + cetak A4 |
| **Pengaturan** | Pengguna & Outlet (admin), Log Aktivitas (audit), Notifikasi in-app (lonceng) |

Semua tabel support **select / select-all + bulk actions** (bulk void, bulk approve, bulk cancel, bulk delete — admin only).

---

## 📘 User Manual

Untuk **user awam**, buka manual lengkap:

- **Browser:** `http://alamat-aplikasi/manual` — ada Daftar Isi, 17 screenshot placeholder, dan tombol **Cetak / Simpan PDF**
- **File:** `USER_MANUAL.md` (Markdown, di repo) — `USER_MANUAL.pdf` & `USER_MANUAL.doc` di root & `public/` (hasil `chrome --print-to-pdf`)

Isi manual: Login & ganti outlet, Dashboard, POS langkah-demi-langkah, Void, Tutup Kasir, Bahan/Menu, Transfer/Opname/Reorder, Karyawan/Kasbon/Gaji, 6 Laporan, Pengaturan, Notifikasi, Instal PWA, SOP harian, FAQ.

> Ward: Buka `/manual` lalu `Ctrl+P` → *Save as PDF* untuk PDF terbaru kapan pun.

---

## 🚀 Instalasi Cepat

```bash
# 1. Clone & install
composer install
cp .env.example .env && php artisan key:generate
npm install

# 2. DB (default sqlite, atau set pgsql di .env)
touch database/database.sqlite
php artisan migrate --seed   # seed: DkriukSeeder + SampleData

# 3. Build & run
npm run build
php artisan serve  # http://127.0.0.1:8000
# Login: lihat database/seeders/DkriukSeeder.php untuk akun default (admin/staff)
```

**Vercel:** `vercel.json` sudah ada — `SESSION_DRIVER=cookie`, `LOG_CHANNEL=stderr`. Pastikan `APP_KEY` & `DB_*` di env Vercel.

---

## 👥 Peran

- **Admin:** semua outlet, ganti outlet di topbar, kelola Pengguna/Outlet/Menu/Bahan, Void, Approve Kasbon, Cancel Transfer.
- **Staff/Kasir:** outlet sendiri terkunci, POS, stok outlet sendiri, ajukan kasbon (Pending), laporan outlet sendiri.

Validasi outlet di server (`OutletContext::selectableOutletOptions()` + `rules()` di form) cegah tamper.

---

## 🔒 Keamanan & Audit

- `UserResource` & `Menu/Ingredient` hanya admin (canCreate/Edit/Delete).
- `StockService` pakai `lockForUpdate` cegah lost-update & double-deduct.
- `SalesTransactionItemObserver` paksa `price = Menu.price` (anti-tamper).
- `ActivityLog` otomatis untuk 10 model + custom `voided/approved/cancelled`.
- `database.sqlite` & `.env` di-`ignore` (git & vercel).

---

## 📸 Screenshots

Placeholder SVG di `public/manual-screenshots/` (17 file, 1200×750, browser chrome mockup). Ganti dengan screenshot asli kapan pun — manual HTML akan otomatis pakai yang baru.

---

## 📄 Lisensi

MIT — lihat `LICENSE`.

*Sedia v1.0 — 25 Aug 2026 — Dibuat dengan Filament & Laravel*
