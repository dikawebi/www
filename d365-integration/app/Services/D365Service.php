<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class D365Service
{
    protected $resource;

    public function __construct()
    {
        $this->resource = rtrim(env('D365_RESOURCE'), '/');
    }

    public function getAccessToken()
    {
        return Cache::remember('d365_token', 3500, function () {
            $response = Http::asForm()->post("https://login.microsoftonline.com/" . env('D365_TENANT_ID') . "/oauth2/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => env('D365_CLIENT_ID'),
                'client_secret' => env('D365_CLIENT_SECRET'),
                'resource'      => $this->resource,
            ]);
            return $response->json()['access_token'];
        });
    }

    public function getFuelConsumption($page = 0, $pageSize = 100, $fromDate = null, $toDate = null, $unit = null, $sortField = 'TransactionDate', $sortOrder = 'desc')
    {
        $token = $this->getAccessToken();
        $pageSize = 100;
        $skip = $page * $pageSize;

        // 1. Base Filter
        $filter = "ItemNumber eq 'FUEL B40' and WBPDTNumber ne ''";

        // 2. Date Filter
        if ($fromDate) { $filter .= " and TransactionDate ge {$fromDate}T00:00:00Z"; }
        if ($toDate) { $filter .= " and TransactionDate le {$toDate}T23:59:59Z"; }

        // 3. Unit Filter (Menggunakan contains agar lebih mudah mencari)
if (!empty($unit)) {
        $cleanUnit = strtoupper(trim($unit));
        $filter .= " and contains(WBPDTNumber, '{$cleanUnit}')";
    }

        // Log untuk Debugging
     //   Log::info("Requesting D365 with Filter: $filter | Sort: $sortField $sortOrder");

        $response = Http::timeout(120)->withToken($token)
            ->get("{$this->resource}/data/InventoryMovementJournalEntriesV4", [
                '$top'      => $pageSize,
                '$skip'     => $skip,
                '$filter'   => $filter,
                '$orderby'  => "$sortField $sortOrder",
                'cross-company' => 'true',
                '$count'   => 'true',
               // '$select'   => 'ItemNumber,JournalNumber,InventoryQuantity,TransactionDate,CostAmount,JournalNameId,InventoryWarehouseId,UnitCost,WBPDTNumber,WBPHMFirst,WBPHMEnd'
               '$select'  => 'JournalNumber,TransactionDate,WBPDTNumber,InventoryQuantity,CostAmount,WBPHMFirst,WBPHMEnd'
            ]);

        return $response->json();
    }
}
