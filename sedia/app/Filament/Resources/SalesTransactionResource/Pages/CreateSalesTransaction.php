<?php

namespace App\Filament\Resources\SalesTransactionResource\Pages;

use App\Exceptions\InsufficientStockException;
use App\Filament\Resources\SalesTransactionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CreateSalesTransaction extends CreateRecord
{
    protected static string $resource = SalesTransactionResource::class;

    /**
     * Filament, secara default, TIDAK membungkus proses "buat record header"
     * + "simpan semua item repeater (relationship)" dalam satu DB transaction
     * (lihat vendor/filament/filament/src/Resources/Pages/CreateRecord.php —
     * handleRecordCreation() dan saveRelationships() dipanggil terpisah, tanpa
     * transaction pembungkus).
     *
     * Tanpa fix ini: kalau item ke-2 dari 3 item gagal (misal stock kurang),
     * item ke-1 yang sudah sukses TETAP PERMANEN tersimpan — transaksi jadi
     * "setengah jadi" (invoice cuma punya sebagian item, total_amount salah).
     *
     * Dengan membungkus seluruh parent::create() dalam DB::transaction(),
     * begitu ADA satu item yang gagal (RuntimeException dari StockService
     * karena stock tidak cukup), SEMUANYA di-rollback bersama — header
     * transaksi, semua item yang sempat kebuat, semua stock movement yang
     * sempat tercatat. Semua-atau-tidak-sama-sekali.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] ??= 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        return $data;
    }

    public function create(bool $another = false): void
    {
        try {
            DB::transaction(fn () => parent::create($another));
        } catch (InsufficientStockException $exception) {
            // Exception khusus ini bawa data terstruktur (nama bahan, outlet,
            // jumlah kurang), jadi notifikasinya bisa disusun jelas buat kasir
            // yang bukan orang teknis — bukan nge-dump pesan exception mentah.
            Notification::make()
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->title('Stok bahan baku tidak mencukupi')
                ->body(
                    ($exception->menuItemName
                        ? "Item \"{$exception->menuItemName}\" tidak bisa disimpan.\n\n"
                        : '').
                    "Transaksi tidak bisa disimpan karena stok bahan \"{$exception->ingredient->name}\" ".
                    "di outlet \"{$exception->outlet->name}\" kurang.\n\n".
                    "Dibutuhkan: {$exception->formattedRequired()}\n".
                    "Stok tersedia: {$exception->formattedAvailable()}\n".
                    "Kurang: {$exception->formattedShortage()}\n\n".
                    'Silakan kurangi jumlah pesanan, atau tambah stok dulu lewat menu Stock Gudang.'
                )
                ->persistent()
                ->send();

            // return biasa cukup di sini — parent::create() sudah gagal total
            // dan di-rollback duluan (lewat DB::transaction), jadi tidak ada
            // proses redirect/notifikasi sukses lain yang perlu "dipaksa berhenti".
            // $this->halt() TIDAK dipakai di sini karena catch(Halt) yang biasanya
            // menangkapnya ada di dalam create() versi ASLI Filament (yang sudah
            // kita timpa total) — kalau dipanggil di sini, Halt itu tidak
            // tertangkap siapa pun dan malah jadi error 500 (ini persis bug
            // yang barusan muncul).
            return;
        } catch (RuntimeException $exception) {
            // Fallback generik untuk error lain yang belum punya exception
            // khusus (misal error tak terduga dari StockService).
            Notification::make()
                ->danger()
                ->title('Transaksi gagal disimpan')
                ->body($exception->getMessage())
                ->persistent()
                ->send();

            return;
        }
    }
}