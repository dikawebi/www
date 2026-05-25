<?php

namespace App\Filament\Resources\FuelConsumptionResource\Pages;

use App\Filament\Resources\FuelConsumptionResource;
use App\Models\FuelConsumption;
use App\Services\D365Service;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListFuelConsumptions extends ListRecords
{
    protected static string $resource = FuelConsumptionResource::class;

    /**
     * Mengambil data dari API D365 dengan dukungan Cache dan Dynamic Pagination
     */
    protected function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        // 1. Ambil State dari UI Filament
        $perPage = $this->getTableRecordsPerPage() ?? 100;
        $page = $this->paginators['page'] ?? 1;
        $search = $this->getTableSearch();
        $sortField = $this->getTableSortColumn() ?? 'TransactionDate';
        $sortOrder = $this->getTableSortDirection() ?? 'desc';

        $filters = $this->tableFilters;
        $fromDate = $filters['TransactionDate']['from_date'] ?? null;
        $toDate = $filters['TransactionDate']['to_date'] ?? null;

        // 2. Buat Cache Key Unik
        $cacheKey = 'd365_fuel_' . md5(serialize([
            $search,
            $sortField,
            $sortOrder,
            $page,
            $perPage, // Sertakan perPage agar cache berubah saat user ganti limit
            $fromDate,
            $toDate
        ]));

        // 3. Eksekusi API via Cache (Hanya panggil SATU kali)
        $response = cache()->remember($cacheKey, 300, function () use ($page, $perPage, $fromDate, $toDate, $search, $sortField, $sortOrder) {
            return app(D365Service::class)->getFuelConsumption(
                page: $page - 1,
                pageSize: $perPage,
                fromDate: $fromDate,
                toDate: $toDate,
                unit: $search,
                sortField: $sortField,
                sortOrder: $sortOrder
            );
        });

        // 4. Ubah Response API menjadi Collection Model
        $items = collect($response['value'] ?? [])->map(function ($item, $index) {
            // ID Unik agar tidak ganda di tampilan
            $item['row_id'] = $item['JournalNumber'] . '-' . ($item['WBPDTNumber'] ?? 'NA') . '-' . $index;
            return new FuelConsumption($item);
        });

        // 5. Ambil Total Count dari OData
        // Jika API tidak mengembalikan count, gunakan logika fallback
        $totalCount = $response['@odata.count'] ?? (
            isset($response['@odata.nextLink'])
                ? ($page * $perPage) + 1
                : (($page - 1) * $perPage) + $items->count()
        );

        // 6. Return Paginator (Gunakan $perPage yang dinamis)
        return new LengthAwarePaginator(
            $items,
            (int) $totalCount,
            (int) $perPage,
            (int) $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
