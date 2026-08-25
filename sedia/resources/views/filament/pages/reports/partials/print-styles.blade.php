{{-- 
    Style khusus halaman laporan — self-contained (tidak bergantung pada utility
    Tailwind yang ter-compile di asset panel Filament). Mencakup layout layar
    + cetak. Disisipkan via partial ini karena panel Filament tidak me-load
    app.css dari Vite.
--}}
<style>
    /* ── Layar: grid KPI — minimal & lega ── */
    .report-kpi-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        margin-top: 1.75rem;
    }

    @media (min-width: 640px) {
        .report-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .report-kpi-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    /* KPI card: sedikit lebih lega & nilai lebih besar */
    .report-kpi-grid .fi-section {
        padding: 1.25rem 1.35rem;
    }

    .report-kpi-grid .fi-section p:first-child {
        letter-spacing: 0.06em;
        font-size: 0.7rem;
    }

    .report-kpi-grid .fi-section p:last-child {
        font-size: 1.6rem;
        line-height: 1.2;
        margin-top: 0.6rem;
    }

    /* ── Layar: grid filter (3 kolom di desktop) ── */
    .report-filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 640px) {
        .report-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .report-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    /* ── Layar: baris aksi filter ── */
    .report-filter-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .report-filter-actions-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    @media (min-width: 640px) {
        .report-filter-actions {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .report-filter-actions-buttons {
            flex-direction: row;
        }
    }

    /* ── Layar: tabel laporan ── */
    .report-table {
        width: 100%;
        font-size: 0.875rem;
        line-height: 1.25rem;
        border-collapse: collapse;
    }

    .report-table thead th,
    .report-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
    }

    .report-table thead th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
        text-align: left;
    }

    .report-table thead th[data-align='right'],
    .report-table tbody td[data-align='right'] {
        text-align: right;
    }

    .report-table thead th[data-align='center'],
    .report-table tbody td[data-align='center'] {
        text-align: center;
    }

    .report-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media screen {
        .report-print-header {
            display: none;
        }

        .report-table thead {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-table thead th {
            color: #6b7280;
        }

        .report-table tbody tr + tr {
            border-top: 1px solid #e5e7eb;
        }

        .report-table tbody tr:hover {
            background-color: #f9fafb;
        }

        :root.dark .report-table thead,
        html.dark .report-table thead,
        .dark .report-table thead {
            background-color: rgb(31 41 55);
            border-bottom-color: rgb(55 65 81);
        }

        :root.dark .report-table thead th,
        html.dark .report-table thead th,
        .dark .report-table thead th {
            color: rgb(156 163 175);
        }

        :root.dark .report-table tbody tr + tr,
        html.dark .report-table tbody tr + tr,
        .dark .report-table tbody tr + tr {
            border-top-color: rgb(55 65 81);
        }

        :root.dark .report-table tbody tr:hover,
        html.dark .report-table tbody tr:hover,
        .dark .report-table tbody tr:hover {
            background-color: rgb(31 41 55);
        }
    }

    @media print {
        @page {
            size: A4;
            margin: 14mm 12mm;
        }

        /* Sembunyikan chrome aplikasi: sidebar, topbar, dan blok filter */
        .fi-topbar,
        .fi-main-sidebar,
        .fi-layout-sidebar-toggle-btn-ctn,
        .fi-sidebar-close-overlay,
        .report-no-print {
            display: none !important;
        }

        /* Konten melebar penuh halaman */
        .fi-layout,
        .fi-main-ctn {
            display: block !important;
        }

        .fi-main {
            max-width: none !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        body {
            background: #fff !important;
        }

        .report-print-header {
            display: block;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #d1d5db;
        }

        .report-print-header .report-print-header-title {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .report-print-header .report-print-header-meta {
            font-size: 0.75rem;
            color: #4b5563;
            margin-top: 0.25rem;
        }

        /* Kualitas layout cetak: jangan potong baris/kartu antar halaman,
           ulangi header tabel kalau tabel menyambung halaman */
        .report-kpi-grid > *,
        .report-table tr {
            break-inside: avoid;
        }

        .report-table thead {
            display: table-header-group;
        }
    }
</style>

<script>
    // Cetak dengan mode terang: kelas `dark` dilepas sementara agar teks putih
    // di layar tidak jadi teks putih di atas kertas, lalu dipulihkan otomatis.
    if (!window.sediaPrintReport) {
        window.sediaPrintReport = function () {
            var root = document.documentElement;
            var wasDark = root.classList.contains('dark');

            if (wasDark) {
                root.classList.remove('dark');
            }

            var restore = function () {
                if (wasDark) {
                    root.classList.add('dark');
                }
                window.removeEventListener('afterprint', restore);
            };

            window.addEventListener('afterprint', restore);
            window.print();
        };
    }
</script>
